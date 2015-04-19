<?php

namespace BitWasp\Bitcoin\Tests;

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\NetworkFactory;

class NetworkTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testCreatesInstance()
    {
        $network = NetworkFactory::create('00', '05', '80', true);
        $this->assertInstanceOf('BitWasp\Bitcoin\Network\NetworkInterface', $network);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage address byte must be 1 hexadecimal byte
     */
    public function testCreateInstanceFailsNotHex()
    {
        NetworkFactory::create('hi', '00', '00', true);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage p2sh byte must be 1 hexadecimal byte
     */
    public function testCreateInstanceFailsP2shNotHex()
    {
        NetworkFactory::create('00', 'hi', '00', true);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage priv byte must be 1 hexadecimal byte
     */
    public function testCreateInstanceFailsPrivNotHex()
    {
        NetworkFactory::create('00', '00', 'hi', true);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Testnet parameter must be a boolean
     */
    public function testCreateInstanceFailsNotBool()
    {
        NetworkFactory::create('aa', '00', 'ab', 'nogood');
    }

    public function testDefaultTestnetFlag()
    {
        $network = NetworkFactory::create('00', '05', '80');
        $this->assertFalse($network->isTestnet());
    }

    public function testGetAddressByte()
    {
        $network = NetworkFactory::create('00', '05', '80', true);
        $this->assertSame('00', $network->getAddressByte());
    }

    public function testGetP2shByte()
    {
        $network = NetworkFactory::create('00', '05', '80', true);
        $this->assertSame('05', $network->getP2shByte());
    }

    /**
     * @expectedException \Exception
     */
    public function testGetNetBytesFailure()
    {
        $network = NetworkFactory::create('00', '05', '80', true);
        $network->getNetMagicBytes();
    }

    public function testGetPrivByte()
    {
        $network = NetworkFactory::create('00', '05', '80', true);
        $this->assertSame('80', $network->getPrivByte());
    }

    public function testCreateTestnet()
    {
        $network = $this->getTestNetwork();
        $this->assertInstanceOf('BitWasp\Bitcoin\Network\NetworkInterface', $network);
        $this->assertInternalType('bool', $network->isTestnet());
        $this->assertTrue($network->isTestnet());
    }

    public function testCreateLivenet()
    {
        $network = $this->getLiveNetwork();
        $this->assertInstanceOf('BitWasp\Bitcoin\Network\NetworkInterface', $network);
        $this->assertInternalType('bool', $network->isTestnet());
        $this->assertFalse($network->isTestnet());
    }

    /**
     * @return \BitWasp\Bitcoin\Network\NetworkInterface
     */
    private function getTestNetwork()
    {
        return NetworkFactory::bitcoinTestnet();
    }

    /**
     * @return \BitWasp\Bitcoin\Network\NetworkInterface
     */
    private function getLiveNetwork()
    {
        return NetworkFactory::bitcoin();
    }

    public function testFactoryPresets()
    {
        $p2sh = new ScriptHashAddress(Buffer::hex("3399bc19f2b20473d417e31472c92947b59f95f8"));
        $p2pk = new PayToPubKeyHashAddress(Buffer::hex("06f1b66ffe49df7fce684df16c62f59dc9adbd3f"));

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

    public function testGetHDPrivByte()
    {
        $network = NetworkFactory::create('00', '05', '80', true);
        $network->setHDPrivByte('0488B21E');
        $this->assertSame('0488B21E', $network->getHDPrivByte());
    }


    public function testSetHDPrivByte()
    {
        $network = NetworkFactory::create('00', '05', '80', true);
        $network->setHDPrivByte('0488B21E');
        $this->assertSame('0488B21E', $network->getHDPrivByte());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage No HD xpub byte was set
     */
    public function testGetHDPubByteException()
    {
        $network = NetworkFactory::create('00', '05', '80', true);
        $network->getHDPubByte();
    }

    public function testGetHDPubByte()
    {
        $network = NetworkFactory::create('00', '05', '80', true);
        $network->setHDPubByte('0488B21E');
        $this->assertSame('0488B21E', $network->getHDPubByte());
    }

    public function testSetHDPubByte()
    {
        $network = NetworkFactory::create('00', '05', '80', true);
        $network->setHDPubByte('0488B21E');
        $this->assertSame('0488B21E', $network->getHDPubByte());
    }
}
