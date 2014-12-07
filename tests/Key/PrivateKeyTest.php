<?php

namespace Bitcoin\Tests\Key;

use Bitcoin\Key\PrivateKey;
use Bitcoin\Network;
use Bitcoin\Util\Buffer;

class PrivateKeyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PrivateKey
     */
    protected $privateKey;

    protected $baseType = 'Bitcoin\Key\PrivateKey';
    protected $publicType = 'Bitcoin\Key\PublicKey';

    public function setUp()
    {
        $this->privateKey = null;
    }

    public function testIsValidKey()
    {
        // Keys must be < the order of the curve
        // Order of secp256k1 - 1

        $key1 = 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364140';
        $this->assertTrue(PrivateKey::isValidKey($key1));

        $key2 = '4141414141414141414141414141414141414141414141414141414141414141';
        $this->assertTrue(PrivateKey::isValidKey($key2));

        $key3 = '8000000000000000000000000000000000000000000000000000000000000000';
        $this->assertTrue(PrivateKey::isValidKey($key3));

        $key4 = '8000000000000000000000000000000000000000000000000000000000000001';
        $this->assertTrue(PrivateKey::isValidKey($key3));

    }

    /**
     * @depends testIsValidKey
     */
    public function testIsValidKeyFailure()
    {
        // Order of secp256k1
        $order = 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141';
        $this->assertFalse(PrivateKey::isValidKey($order));

        $key1 = '0000000000000000000000000000000000000000000000000000000000000000';
        $this->assertFalse(PrivateKey::isValidKey($key1));
    }

    public function testCreatePrivateKey()
    {
        $this->privateKey = new PrivateKey('4141414141414141414141414141414141414141414141414141414141414141');
        $this->assertInstanceOf($this->baseType, $this->privateKey);
        $this->assertSame($this->privateKey->serialize('hex'), '4141414141414141414141414141414141414141414141414141414141414141');
        $this->assertFalse($this->privateKey->isCompressed());
        $this->assertTrue($this->privateKey->isPrivate());
        $this->assertInstanceOf($this->publicType, $this->privateKey->getPublicKey());
        $this->assertSame(
            '04eec7245d6b7d2ccb30380bfbe2a3648cd7a942653f5aa340edcea1f2836866198bd9fc8678e246f23f40bfe8d928d3f37a51642aed1d5b471a1a0db4f71891ea',
            $this->privateKey->getPublicKey()->serialize('hex')
        );

        //$this->assertSame(
          //  'b5bd079c4d57cc7fc28ecf8213a6b791625b8183',
            //$this->privateKey->getPublicKey()->getPubKeyHash()
        //);
    }

    /**
     * @expectedException \Exception
     */
    public function testCreatePrivateKeyFailure()
    {
        $this->privateKey = new PrivateKey('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141');
    }

    public function testGenerateNewUncompressed()
    {
        $this->privateKey = PrivateKey::generateNew(false);
        $this->assertInstanceOf($this->baseType, $this->privateKey);
        $this->assertFalse($this->privateKey->isCompressed());
        $this->assertTrue($this->privateKey->isPrivate());
        $this->assertInstanceOf($this->publicType, $this->privateKey->getPublicKey());
    }

    public function testSetCompressed()
    {
        $this->privateKey = new PrivateKey('4141414141414141414141414141414141414141414141414141414141414141');
        $this->assertFalse($this->privateKey->isCompressed());
        $this->privateKey->setCompressed(true);
        $this->assertTrue($this->privateKey->isCompressed());
    }


    public function testGenerateNewCompressed()
    {
        $this->privateKey = PrivateKey::generateNew(true);
        $this->assertInstanceOf($this->baseType, $this->privateKey);
        $this->assertTrue($this->privateKey->isCompressed());
        $this->assertTrue($this->privateKey->isPrivate());
        $this->assertInstanceOf($this->publicType, $this->privateKey->getPublicKey());
    }

    public function testGetWif()
    {
        $this->privateKey = new PrivateKey('4141414141414141414141414141414141414141414141414141414141414141');
        $network = new Network('00','05','80');
        $this->assertSame($this->privateKey->getWif($network), '5JK2Rv7ZquC9J11AQZXXU7M9S17z193GPjsKPU3gSANJszAW3dU');

        $this->privateKey->setCompressed(true);
        $this->assertSame($this->privateKey->getWif($network), 'KyQZJyRyxqNBc31iWzZjUf1vDMXpbcUzwND6AANq44M3v38smDkA');
    }

    public function testGetPubKeyHash()
    {
        $this->privateKey = new PrivateKey('4141414141414141414141414141414141414141414141414141414141414141');
        $this->assertSame('d00baafc1c7f120ab2ae0aa22160b516cfcf9cfe', $this->privateKey->getPubKeyHash());
        $this->privateKey->setCompressed(true);
        $this->assertSame('c53c82d3357f1f299330d585907b7c64b6b7a5f0', $this->privateKey->getPubKeyHash());
    }

    public function testGetDefaultCurve()
    {
        $this->privateKey = new PrivateKey('4141414141414141414141414141414141414141414141414141414141414141');
        $curve = $this->privateKey->getCurve();

        $this->assertSame($curve->getA(), 0);
        $this->assertSame($curve->getB(), 7);
        $this->assertSame($curve->getPrime(), '115792089237316195423570985008687907853269984665640564039457584007908834671663');
    }

    public function testSerialize()
    {
        $buf                = Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141');
        $this->privateKey   = new PrivateKey($buf);
        $this->assertSame($buf->serialize(), $this->privateKey->serialize());
    }

    public function test__toString()
    {
        $hex                = '4141414141414141414141414141414141414141414141414141414141414141';
        $buf                = Buffer::hex($hex);
        $this->privateKey   = new PrivateKey($buf);
        $this->assertEquals($hex, $this->privateKey->__toString());
    }

    public function testGetSize()
    {
        $hex                = '4141414141414141414141414141414141414141414141414141414141414141';
        $buf                = Buffer::hex($hex);
        $this->privateKey   = new PrivateKey($buf);
        $this->assertEquals(32, $this->privateKey->getSize());
        $this->assertEquals(64, $this->privateKey->getSize('hex'));
    }
}
 