<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * see https://github.com/bitpay/php-bitpay-client/blob/master/LICENSE
 */

namespace Bitcoin;

class NetworkTest extends \PHPUnit_Framework_TestCase
{
    protected $network;

    public function setUp()
    {
        $this->network = null;
    }

    public function testCreatesInstance()
    {
        $this->network = new \Bitcoin\Network('00','05','80',true);
        $this->assertInstanceOf('Bitcoin\NetworkInterface', $this->network);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage address_byte must be 1 hexadecimal byte
     */
    public function testCreateInstanceFailsNotHex()
    {
        $this->network = new \Bitcoin\Network('hi','00','00', true);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Testnet parameter must be a boolean
     */
    public function testCreateInstanceFailsNotBool()
    {
        $this->network = new \Bitcoin\Network('aa','00','ab', 'nogood');
    }

    public function testDefaultTestnetFlag()
    {
        $this->network = new \Bitcoin\Network('00','05','80');
        $this->assertFalse($this->network->isTestnet());
    }

    public function testGetAddressByte()
    {
        $this->network = new \Bitcoin\Network('00','05','80',true);
        $this->assertSame('00', $this->network->getAddressByte());
    }

    public function testGetP2shByte()
    {
        $this->network = new \Bitcoin\Network('00','05','80',true);
        $this->assertSame('05', $this->network->getP2shByte());
    }

    public function testGetPrivByte()
    {
        $this->network = new \Bitcoin\Network('00','05','80',true);
        $this->assertSame('80', $this->network->getPrivByte());
    }

    public function testCreateTestnet()
    {
        $this->network = $this->getTestNetwork();
        $this->assertInstanceOf('Bitcoin\NetworkInterface', $this->network);
        $this->assertInternalType('bool', $this->network->isTestnet());
        $this->assertTrue($this->network->isTestnet());
    }

    public function testCreateLivenet()
    {
        $this->network = $this->getLiveNetwork();
        $this->assertInstanceOf('Bitcoin\NetworkInterface', $this->network);
        $this->assertInternalType('bool', $this->network->isTestnet());
        $this->assertFalse($this->network->isTestnet());
    }


    private function getTestNetwork()
    {
        return new Network('00','05','80',true);
    }

    private function getLiveNetwork()
    {
        return new Network('00','05','80',false);
    }

}