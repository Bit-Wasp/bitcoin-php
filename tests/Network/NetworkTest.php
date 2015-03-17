<?php

namespace Afk11\Bitcoin\Tests;

use Afk11\Bitcoin\Address\PayToPubKeyHashAddress;
use Afk11\Bitcoin\Address\ScriptHashAddress;
use Afk11\Bitcoin\Network\Network;
use Afk11\Bitcoin\Network\NetworkFactory;

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
        $this->assertInstanceOf('Afk11\Bitcoin\Network\NetworkInterface', $this->network);
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
        $this->assertInstanceOf('Afk11\Bitcoin\Network\NetworkInterface', $this->network);
        $this->assertInternalType('bool', $this->network->isTestnet());
        $this->assertTrue($this->network->isTestnet());
    }

    public function testCreateLivenet()
    {
        $this->network = $this->getLiveNetwork();
        $this->assertInstanceOf('Afk11\Bitcoin\Network\NetworkInterface', $this->network);
        $this->assertInternalType('bool', $this->network->isTestnet());
        $this->assertFalse($this->network->isTestnet());
    }


    private function getTestNetwork()
    {
        return NetworkFactory::bitcoinTestnet();
    }

    private function getLiveNetwork()
    {
        return NetworkFactory::bitcoin();
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

    public function testFactoryPresets()
    {
        $p2sh = new ScriptHashAddress("3399bc19f2b20473d417e31472c92947b59f95f8");
        $p2pk = new PayToPubKeyHashAddress("06f1b66ffe49df7fce684df16c62f59dc9adbd3f");

        $this->assertEquals(NetworkFactory::bitcoin()->getAddressByte(), '00');
        $this->assertEquals(NetworkFactory::bitcoin()->getP2shByte(), '05');
        $this->assertEquals(NetworkFactory::bitcoin()->getPrivByte(), '80');
        $this->assertEquals(NetworkFactory::bitcoin()->isTestnet(), false);
        $this->assertEquals(NetworkFactory::bitcoin()->getHDPrivByte(), '0488ade4');
        $this->assertEquals(NetworkFactory::bitcoin()->getHDPubByte(), '0488b21e');
        $this->assertEquals(NetworkFactory::bitcoin()->getNetMagicBytes(), 'd9b4bef9');
        $this->assertEquals("36PrZ1KHYMpqSyAQXSG8VwbUiq2EogxLo2", $p2sh->getAddress(NetworkFactory::bitcoin()));
        $this->assertEquals("1dice8EMZmqKvrGE4Qc9bUFf9PX3xaYDp", $p2pk->getAddress(NetworkFactory::bitcoin()));

        $this->assertEquals(NetworkFactory::bitcoinTestnet()->getAddressByte(), '6f');
        $this->assertEquals(NetworkFactory::bitcoinTestnet()->getP2shByte(), 'c4');
        $this->assertEquals(NetworkFactory::bitcoinTestnet()->getPrivByte(), 'ef');
        $this->assertEquals(NetworkFactory::bitcoinTestnet()->isTestnet(), true);
        $this->assertEquals(NetworkFactory::bitcoinTestnet()->getHDPrivByte(), '04358394');
        $this->assertEquals(NetworkFactory::bitcoinTestnet()->getHDPubByte(), '043587cf');
        $this->assertEquals(NetworkFactory::bitcoinTestnet()->getNetMagicBytes(), 'd9b4bef9');

        $this->assertEquals("2Mwx4ckFK9pLBeknxCZt17tajwBEQXxNaWV", $p2sh->getAddress(NetworkFactory::bitcoinTestnet()));
        $this->assertEquals("mg9fuhDDAbD673KswdNyyWgaX8zDxJT8QY", $p2pk->getAddress(NetworkFactory::bitcoinTestnet()));

        $this->assertEquals(NetworkFactory::litecoin()->getAddressByte(), '30');
        $this->assertEquals(NetworkFactory::litecoin()->getP2shByte(), '05');
        $this->assertEquals(NetworkFactory::litecoin()->getPrivByte(), 'b0');
        $this->assertEquals(NetworkFactory::litecoin()->isTestnet(), false);
        $this->assertEquals(NetworkFactory::litecoin()->getHDPrivByte(), '019d9cfe');
        $this->assertEquals(NetworkFactory::litecoin()->getHDPubByte(), '019da462');
        $this->assertEquals(NetworkFactory::litecoin()->getNetMagicBytes(), 'd9b4bef9');

        $this->assertEquals("36PrZ1KHYMpqSyAQXSG8VwbUiq2EogxLo2", $p2sh->getAddress(NetworkFactory::litecoin()));
        $this->assertEquals("LKrfsrS4SE1tajYRQCPuRcY1sMkoFf1BN3", $p2pk->getAddress(NetworkFactory::litecoin()));

        $this->assertEquals(NetworkFactory::viacoin()->getAddressByte(), '47');
        $this->assertEquals(NetworkFactory::viacoin()->getP2shByte(), '21');
        $this->assertEquals(NetworkFactory::viacoin()->getPrivByte(), 'c7');
        $this->assertEquals(NetworkFactory::viacoin()->isTestnet(), false);
        $this->assertEquals(NetworkFactory::viacoin()->getHDPrivByte(), '0488ade4');
        $this->assertEquals(NetworkFactory::viacoin()->getHDPubByte(), '0488b21e');
        $this->assertEquals(NetworkFactory::viacoin()->getNetMagicBytes(), 'cbc6680f');
        $this->assertEquals("EMrk83fMRQoNM74qDBb45TDWLxEehWXA7u", $p2sh->getAddress(NetworkFactory::viacoin()));
        $this->assertEquals("VadYXMHgmNg3PhkQxr4EaVo7LxgVZvhAdc", $p2pk->getAddress(NetworkFactory::viacoin()));

        $this->assertEquals(NetworkFactory::viacoinTestnet()->getAddressByte(), '7f');
        $this->assertEquals(NetworkFactory::viacoinTestnet()->getP2shByte(), 'c4');
        $this->assertEquals(NetworkFactory::viacoinTestnet()->getPrivByte(), 'ff');
        $this->assertEquals(NetworkFactory::viacoinTestnet()->isTestnet(), true);
        $this->assertEquals(NetworkFactory::viacoinTestnet()->getHDPrivByte(), '04358394');
        $this->assertEquals(NetworkFactory::viacoinTestnet()->getHDPubByte(), '043587cf');
        $this->assertEquals(NetworkFactory::viacoinTestnet()->getNetMagicBytes(), 'cbc6680f');
        $this->assertEquals("2Mwx4ckFK9pLBeknxCZt17tajwBEQXxNaWV", $p2sh->getAddress(NetworkFactory::viacoinTestnet()));
        $this->assertEquals("t7ZKfRypXUd7ByZGLLi5jX3AbD7KQvDj4a", $p2pk->getAddress(NetworkFactory::viacoinTestnet()));
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
