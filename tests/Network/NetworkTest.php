<?php

namespace BitWasp\Bitcoin\Tests\Network;

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\NetworkFactory;

class NetworkTest extends AbstractTestCase
{
    /**
     *
     */
    public function testCreatesInstance()
    {
        $network = NetworkFactory::create('00', '05', '80', true);
        $this->assertInstanceOf($this->netInterfaceType, $network);
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
        $this->assertInstanceOf($this->netInterfaceType, $network);
        $this->assertInternalType('bool', $network->isTestnet());
        $this->assertTrue($network->isTestnet());
    }

    public function testCreateLivenet()
    {
        $network = $this->getLiveNetwork();
        $this->assertInstanceOf($this->netInterfaceType, $network);
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
        $p2sh = new ScriptHashAddress(Buffer::hex('3399bc19f2b20473d417e31472c92947b59f95f8'));
        $p2pk = new PayToPubKeyHashAddress(Buffer::hex('06f1b66ffe49df7fce684df16c62f59dc9adbd3f'));

        $this->assertEquals(NetworkFactory::bitcoin()->getAddressByte(), '00');
        $this->assertEquals(NetworkFactory::bitcoin()->getP2shByte(), '05');
        $this->assertEquals(NetworkFactory::bitcoin()->getPrivByte(), '80');
        $this->assertEquals(NetworkFactory::bitcoin()->isTestnet(), false);
        $this->assertEquals(NetworkFactory::bitcoin()->getHDPrivByte(), '0488ade4');
        $this->assertEquals(NetworkFactory::bitcoin()->getHDPubByte(), '0488b21e');
        $this->assertEquals(NetworkFactory::bitcoin()->getNetMagicBytes(), 'd9b4bef9');
        $this->assertEquals('36PrZ1KHYMpqSyAQXSG8VwbUiq2EogxLo2', $p2sh->getAddress(NetworkFactory::bitcoin()));
        $this->assertEquals('1dice8EMZmqKvrGE4Qc9bUFf9PX3xaYDp', $p2pk->getAddress(NetworkFactory::bitcoin()));


        $this->assertEquals(NetworkFactory::bitcoinTestnet()->getAddressByte(), '6f');
        $this->assertEquals(NetworkFactory::bitcoinTestnet()->getP2shByte(), 'c4');
        $this->assertEquals(NetworkFactory::bitcoinTestnet()->getPrivByte(), 'ef');
        $this->assertEquals(NetworkFactory::bitcoinTestnet()->isTestnet(), true);
        $this->assertEquals(NetworkFactory::bitcoinTestnet()->getHDPrivByte(), '04358394');
        $this->assertEquals(NetworkFactory::bitcoinTestnet()->getHDPubByte(), '043587cf');
        $this->assertEquals(NetworkFactory::bitcoinTestnet()->getNetMagicBytes(), '0709110b');

        $this->assertEquals('2Mwx4ckFK9pLBeknxCZt17tajwBEQXxNaWV', $p2sh->getAddress(NetworkFactory::bitcoinTestnet()));
        $this->assertEquals('mg9fuhDDAbD673KswdNyyWgaX8zDxJT8QY', $p2pk->getAddress(NetworkFactory::bitcoinTestnet()));

        $this->assertEquals(NetworkFactory::litecoin()->getAddressByte(), '30');
        $this->assertEquals(NetworkFactory::litecoin()->getP2shByte(), '05');
        $this->assertEquals(NetworkFactory::litecoin()->getPrivByte(), 'b0');
        $this->assertEquals(NetworkFactory::litecoin()->isTestnet(), false);
        $this->assertEquals(NetworkFactory::litecoin()->getHDPrivByte(), '019d9cfe');
        $this->assertEquals(NetworkFactory::litecoin()->getHDPubByte(), '019da462');
        $this->assertEquals(NetworkFactory::litecoin()->getNetMagicBytes(), 'dbb6c0fb');

        $this->assertEquals('36PrZ1KHYMpqSyAQXSG8VwbUiq2EogxLo2', $p2sh->getAddress(NetworkFactory::litecoin()));
        $this->assertEquals('LKrfsrS4SE1tajYRQCPuRcY1sMkoFf1BN3', $p2pk->getAddress(NetworkFactory::litecoin()));

        $this->assertEquals(NetworkFactory::viacoin()->getAddressByte(), '47');
        $this->assertEquals(NetworkFactory::viacoin()->getP2shByte(), '21');
        $this->assertEquals(NetworkFactory::viacoin()->getPrivByte(), 'c7');
        $this->assertEquals(NetworkFactory::viacoin()->isTestnet(), false);
        $this->assertEquals(NetworkFactory::viacoin()->getHDPrivByte(), '0488ade4');
        $this->assertEquals(NetworkFactory::viacoin()->getHDPubByte(), '0488b21e');
        $this->assertEquals(NetworkFactory::viacoin()->getNetMagicBytes(), 'cbc6680f');
        $this->assertEquals('EMrk83fMRQoNM74qDBb45TDWLxEehWXA7u', $p2sh->getAddress(NetworkFactory::viacoin()));
        $this->assertEquals('VadYXMHgmNg3PhkQxr4EaVo7LxgVZvhAdc', $p2pk->getAddress(NetworkFactory::viacoin()));

        $this->assertEquals(NetworkFactory::viacoinTestnet()->getAddressByte(), '7f');
        $this->assertEquals(NetworkFactory::viacoinTestnet()->getP2shByte(), 'c4');
        $this->assertEquals(NetworkFactory::viacoinTestnet()->getPrivByte(), 'ff');
        $this->assertEquals(NetworkFactory::viacoinTestnet()->isTestnet(), true);
        $this->assertEquals(NetworkFactory::viacoinTestnet()->getHDPrivByte(), '04358394');
        $this->assertEquals(NetworkFactory::viacoinTestnet()->getHDPubByte(), '043587cf');
        $this->assertEquals(NetworkFactory::viacoinTestnet()->getNetMagicBytes(), '92efc5a9');
        $this->assertEquals('2Mwx4ckFK9pLBeknxCZt17tajwBEQXxNaWV', $p2sh->getAddress(NetworkFactory::viacoinTestnet()));
        $this->assertEquals('t7ZKfRypXUd7ByZGLLi5jX3AbD7KQvDj4a', $p2pk->getAddress(NetworkFactory::viacoinTestnet()));

        $this->assertInstanceOf($this->netInterfaceType, NetworkFactory::litecoinTestnet());

        $this->assertEquals(NetworkFactory::dogecoin()->getAddressByte(), '1e');
        $this->assertEquals(NetworkFactory::dogecoin()->getP2shByte(), '16');
        $this->assertEquals(NetworkFactory::dogecoin()->getPrivByte(), '9e');
        $this->assertEquals(NetworkFactory::dogecoin()->isTestnet(), false);
        $this->assertEquals(NetworkFactory::dogecoin()->getHDPrivByte(), '02fac398');
        $this->assertEquals(NetworkFactory::dogecoin()->getHDPubByte(), '02facafd');
        $this->assertEquals(NetworkFactory::dogecoin()->getNetMagicBytes(), 'c0c0c0c0');

        $this->assertEquals('9w97HrPBcRhjMLXswZvYk5DrRQQGvT2UeH', $p2sh->getAddress(NetworkFactory::dogecoin()));
        $this->assertEquals('D5mp9u4seyg7rw2rxeQAhMdrYH7pPs5gNu', $p2pk->getAddress(NetworkFactory::dogecoin()));

        $this->assertEquals(NetworkFactory::dogecoinTestnet()->getAddressByte(), '71');
        $this->assertEquals(NetworkFactory::dogecoinTestnet()->getP2shByte(), 'c4');
        $this->assertEquals(NetworkFactory::dogecoinTestnet()->getPrivByte(), 'f1');
        $this->assertEquals(NetworkFactory::dogecoinTestnet()->isTestnet(), true);
        $this->assertEquals(NetworkFactory::dogecoinTestnet()->getHDPrivByte(), '0432a243');
        $this->assertEquals(NetworkFactory::dogecoinTestnet()->getHDPubByte(), '043587cf');
        $this->assertEquals(NetworkFactory::dogecoinTestnet()->getNetMagicBytes(), 'c0c0c0c0');

        $this->assertEquals('2Mwx4ckFK9pLBeknxCZt17tajwBEQXxNaWV', $p2sh->getAddress(NetworkFactory::dogecoinTestnet()));
        $this->assertEquals('nUpssuonax8qjuc3zU3cwmE9n9W7QXJjgW', $p2pk->getAddress(NetworkFactory::dogecoinTestnet()));

        // Dash
        $this->assertEquals(NetworkFactory::dash()->getAddressByte(), '4c');
        $this->assertEquals(NetworkFactory::dash()->getP2shByte(), '10');
        $this->assertEquals(NetworkFactory::dash()->getPrivByte(), 'cc');
        $this->assertEquals(NetworkFactory::dash()->isTestnet(), false);
        $this->assertEquals(NetworkFactory::dash()->getHDPrivByte(), '02fe52cc');
        $this->assertEquals(NetworkFactory::dash()->getHDPubByte(), '02fe52f8');
        $this->assertEquals(NetworkFactory::dash()->getNetMagicBytes(), 'bd6b0cbf');
        $this->assertEquals('7X7VPCbTMLvUSjhMo3vdqKb8eNrccxgkJ1', $p2sh->getAddress(NetworkFactory::dash()));
        $this->assertEquals('XbKZStn8KGzRUsSr5wiq18A3VUyD7pdKXX', $p2pk->getAddress(NetworkFactory::dash()));

        // Dash testnet
        $this->assertEquals(NetworkFactory::dashTestnet()->getAddressByte(), '8b');
        $this->assertEquals(NetworkFactory::dashTestnet()->getP2shByte(), '13');
        $this->assertEquals(NetworkFactory::dashTestnet()->getPrivByte(), 'ef');
        $this->assertEquals(NetworkFactory::dashTestnet()->isTestnet(), true);
        $this->assertEquals(NetworkFactory::dashTestnet()->getHDPrivByte(), '3a805837');
        $this->assertEquals(NetworkFactory::dashTestnet()->getHDPubByte(), '3a8061a0');
        $this->assertEquals(NetworkFactory::dashTestnet()->getNetMagicBytes(), 'ffcae2ce');
        $this->assertEquals('8j8JLXVKUtK6u37csJvbHhQVXtdSmwYhAb', $p2sh->getAddress(NetworkFactory::dashTestnet()));
        $this->assertEquals('xwcZUjZH3eBd1BEJdNhuZ2Jc9GCduoV5cV', $p2pk->getAddress(NetworkFactory::dashTestnet()));
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
