<?php

namespace BitWasp\Bitcoin\Tests;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Network\NetworkFactory;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Math\Gmp;

class BitcoinTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Bitcoin::setMath(new Math());
        Bitcoin::setGenerator(EccFactory::getSecgCurves(Bitcoin::getMath())->generator256k1());
        Bitcoin::setNetwork(Bitcoin::getDefaultNetwork()); // (re)set back to default
    }

    public function testGetMath()
    {
        $default = Bitcoin::getMath();
        $this->assertEquals($default, Bitcoin::getMath());
    }

    public function testGetGenerator()
    {
        $default = EccFactory::getSecgCurves(Bitcoin::getMath())->generator256k1();
        $chosen = EccFactory::getNistCurves(Bitcoin::getMath())->generator192();
        $this->assertEquals($default, Bitcoin::getGenerator());
        Bitcoin::setGenerator($chosen);
        $this->assertEquals($chosen, Bitcoin::getGenerator());
    }

    public function testGetNetwork()
    {
        $default = Bitcoin::getDefaultNetwork();
        $bitcoin = NetworkFactory::bitcoin();
        $viacoin = NetworkFactory::viacoin();

        $this->assertEquals($default, $bitcoin);
        $this->assertEquals($default, Bitcoin::getNetwork());
        Bitcoin::setNetwork($viacoin);
        $this->assertSame($viacoin, Bitcoin::getNetwork());
    }
}
