<?php

namespace Bitcoin\Tests;

use Bitcoin\Network;

class NetworkTest extends \PHPUnit_Framework_TestCase
{
    protected $network;

    public function setUp()
    {
        $this->network = null;
    }

    public function testCreatesInstance()
    {
        $this->network = new Network('00', '05', '80', true);
        $this->assertInstanceOf('Bitcoin\NetworkInterface', $this->network);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage address byte must be 1 hexadecimal byte
     */
    public function testCreateInstanceFailsNotHex()
    {
        $this->network = new Network('hi', '00', '00', true);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage p2sh byte must be 1 hexadecimal byte
     */
    public function testCreateInstanceFailsP2shNotHex()
    {
        $this->network = new Network('00', 'hi', '00', true);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage priv byte must be 1 hexadecimal byte
     */
    public function testCreateInstanceFailsPrivNotHex()
    {
        $this->network = new Network('00', '00', 'hi', true);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Testnet parameter must be a boolean
     */
    public function testCreateInstanceFailsNotBool()
    {
        $this->network = new Network('aa', '00', 'ab', 'nogood');
    }

    public function testDefaultTestnetFlag()
    {
        $this->network = new Network('00', '05', '80');
        $this->assertFalse($this->network->isTestnet());
    }

    public function testGetAddressByte()
    {
        $this->network = new Network('00', '05', '80', true);
        $this->assertSame('00', $this->network->getAddressByte());
    }

    public function testGetP2shByte()
    {
        $this->network = new Network('00', '05', '80', true);
        $this->assertSame('05', $this->network->getP2shByte());
    }

    public function testGetPrivByte()
    {
        $this->network = new Network('00', '05', '80', true);
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
        return new Network('00', '05', '80', true);
    }

    private function getLiveNetwork()
    {
        return new Network('00', '05', '80', false);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage No HD xpriv byte was set
     */
    public function testGetHDPrivByteException()
    {
        $this->network = new Network('00', '05', '80', true);
        $this->network->getHDPrivByte();
    }

    /**
     * @depends testGetHDPrivByteException
     */
    public function testGetHDPrivByte()
    {
        $this->network = new Network('00', '05', '80', true);
        $this->network->setHDPrivByte('0488B21E');
        $this->assertSame('0488B21E', $this->network->getHDPrivByte());
    }

    /**
     * @depends testGetHDPrivByteException
     */
    public function testSetHDPrivByte()
    {
        $this->network = new Network('00', '05', '80', true);
        $this->network->setHDPrivByte('0488B21E');
        $this->assertSame('0488B21E', $this->network->getHDPrivByte());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage No HD xpub byte was set
     */
    public function testGetHDPubByteException()
    {
        $this->network = new Network('00', '05', '80', true);
        $this->network->getHDPubByte();
    }
    /**
     * @depends testGetHDPubByteException
     */
    public function testGetHDPubByte()
    {
        $this->network = new Network('00', '05', '80', true);
        $this->network->setHDPubByte('0488B21E');
        $this->assertSame('0488B21E', $this->network->getHDPubByte());
    }

    /**
     * @depends testGetHDPubByteException
     */
    public function testSetHDPubByte()
    {
        $this->network = new Network('00', '05', '80', true);
        $this->network->setHDPubByte('0488B21E');
        $this->assertSame('0488B21E', $this->network->getHDPubByte());
    }
}
