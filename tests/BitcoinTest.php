<?php

namespace Afk11\Bitcoin\Tests;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\Network\Network;
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
    }
    public function testGetMath()
    {
        $bitcoin = new Bitcoin;
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
        $default = new Network('00', '05', '80');
        $default
            ->setHDPubByte('0488b21e')
            ->setHDPrivByte('0488ade4')
            ->setNetMagicBytes('d9b4bef9');

        $custom = new Network('fc','fd','00');

        $this->assertEquals($default, Bitcoin::getNetwork());
        Bitcoin::setNetwork($custom);
        $this->assertSame($custom, Bitcoin::getNetwork());
    }
}