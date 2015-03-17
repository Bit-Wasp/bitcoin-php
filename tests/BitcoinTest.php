<?php

namespace Afk11\Bitcoin\Tests;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\Network\Network;
use Afk11\Bitcoin\Network\NetworkFactory;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Math\Gmp;

class BitcoinTest extends \PHPUnit_Framework_TestCase
{
    public function restore()
    {


    }
    public function setUp()
    {
    }

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
