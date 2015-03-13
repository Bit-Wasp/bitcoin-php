<?php

namespace Afk11\Bitcoin\Tests\Key;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Key\PrivateKey;
use Afk11\Bitcoin\Network;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\Key\PrivateKeyFactory;
use Mdanter\Ecc\GeneratorPoint;

class PrivateKeyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PrivateKey
     */
    protected $privateKey;

    /**
     * @var Math
     */
    protected $math;

    /**
     * @var GeneratorPoint
     */
    protected $generator;

    /**
     * @var string
     */
    protected $baseType = 'Afk11\Bitcoin\Key\PrivateKey';

    /**
     * @var string
     */
    protected $publicType = 'Afk11\Bitcoin\Key\PublicKey';

    /**
     *
     */
    public function setUp()
    {
        $this->privateKey = null;
        $this->math = Bitcoin::getMath();
        $this->generator = Bitcoin::getGenerator();
    }

    public function testIsValidKey()
    {
        // Keys must be < the order of the curve
        // Order of secp256k1 - 1

        $key1 = $this->math->hexDec('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364140');
        $this->assertTrue(PrivateKey::isValidKey($key1));

        $key2 = $this->math->hexDec('4141414141414141414141414141414141414141414141414141414141414141');
        $this->assertTrue(PrivateKey::isValidKey($key2));

        $key3 = $this->math->hexDec('8000000000000000000000000000000000000000000000000000000000000000');
        $this->assertTrue(PrivateKey::isValidKey($key3));

        $key4 = $this->math->hexDec('8000000000000000000000000000000000000000000000000000000000000001');
        $this->assertTrue(PrivateKey::isValidKey($key4));

    }

    /**
     * @depends testIsValidKey
     */
    public function testIsValidKeyFailure()
    {
        // Order of secp256k1
        $order = $this->math->hexDec('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141');
        $this->assertFalse(PrivateKey::isValidKey($order));

        $key1 = $this->math->hexDec('0000000000000000000000000000000000000000000000000000000000000000');
        $this->assertFalse(PrivateKey::isValidKey($key1));
    }

    public function testCreatePrivateKey()
    {
        $hex = '4141414141414141414141414141414141414141414141414141414141414141';
        $key = $this->math->hexDec($hex);
        $this->privateKey   = new PrivateKey($this->math, $this->generator, $key);

        $this->assertInstanceOf($this->baseType, $this->privateKey);
        $this->assertSame($this->privateKey->getBuffer()->serialize('hex'), '4141414141414141414141414141414141414141414141414141414141414141');
        $this->assertFalse($this->privateKey->isCompressed());
        $this->assertTrue($this->privateKey->isPrivate());
        $this->assertInstanceOf($this->publicType, $this->privateKey->getPublicKey());
        $this->assertSame(
            '04eec7245d6b7d2ccb30380bfbe2a3648cd7a942653f5aa340edcea1f2836866198bd9fc8678e246f23f40bfe8d928d3f37a51642aed1d5b471a1a0db4f71891ea',
            $this->privateKey->getPublicKey()->getBuffer()->serialize('hex')
        );
    }

    /**
     * @expectedException \Exception
     */
    public function testCreatePrivateKeyFailure()
    {
        $dec = $this->math->hexDec('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141');
        $this->privateKey = new PrivateKey($this->math, $this->generator, $dec);
    }

    public function testGenerateNewUncompressed()
    {
        $this->privateKey = PrivateKeyFactory::create(false);
        $this->assertInstanceOf($this->baseType, $this->privateKey);
        $this->assertFalse($this->privateKey->isCompressed());
        $this->assertTrue($this->privateKey->isPrivate());
        $this->assertInstanceOf($this->publicType, $this->privateKey->getPublicKey());
    }

    public function testSetCompressed()
    {
        $dec = $this->math->hexDec('4141414141414141414141414141414141414141414141414141414141414141');
        $this->privateKey = new PrivateKey($this->math, $this->generator, $dec);
        $this->assertFalse($this->privateKey->isCompressed());
        $this->privateKey->setCompressed(true);
        $this->assertTrue($this->privateKey->isCompressed());
    }


    public function testGenerateNewCompressed()
    {
        $this->privateKey = PrivateKeyFactory::create(true);
        $this->assertInstanceOf($this->baseType, $this->privateKey);
        $this->assertTrue($this->privateKey->isCompressed());
        $this->assertTrue($this->privateKey->isPrivate());
        $this->assertInstanceOf($this->publicType, $this->privateKey->getPublicKey());
    }

    public function testGetWif()
    {
        $this->privateKey = new PrivateKey($this->math, $this->generator, $this->math->hexDec('4141414141414141414141414141414141414141414141414141414141414141'));
        $network = new Network('00', '05', '80');
        $this->assertSame($this->privateKey->toWif($network), '5JK2Rv7ZquC9J11AQZXXU7M9S17z193GPjsKPU3gSANJszAW3dU');

        $this->privateKey->setCompressed(true);
        $this->assertSame($this->privateKey->toWif($network), 'KyQZJyRyxqNBc31iWzZjUf1vDMXpbcUzwND6AANq44M3v38smDkA');
    }

    public function testGetPubKeyHash()
    {
        $this->privateKey = new PrivateKey($this->math, $this->generator, $this->math->hexDec('4141414141414141414141414141414141414141414141414141414141414141'));
        $this->assertSame('d00baafc1c7f120ab2ae0aa22160b516cfcf9cfe', $this->privateKey->getPubKeyHash());
        $this->privateKey->setCompressed(true);
        $this->assertSame('c53c82d3357f1f299330d585907b7c64b6b7a5f0', $this->privateKey->getPubKeyHash());
    }

    public function testSerialize()
    {
        $this->privateKey   = PrivateKeyFactory::fromHex('4141414141414141414141414141414141414141414141414141414141414141');
        $this->assertSame('4141414141414141414141414141414141414141414141414141414141414141', $this->privateKey->getBuffer()->serialize('hex'));
    }

    public function testFromWif()
    {
        $math = $this->math;

        $regular = array(
            '5KeNtJ66K7UNpirG3574f9Z8SjPDPTc5YaSBczttdoqNdQMK5b9' => 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b',
            '5J6B9UWZSxwHuJF3jv1zi2ZxMAVhA7bBvFFcZXFo7ga1UdgNtDs' => '2413fb3709b05939f04cf2e92f7d0897fc2596f9ad0b8a9ea855c7bfebaae892',
            '5JKQJXqLFxQ9JSw2Wc4Z5ZY1v1BR8u4BfndtXZd1Kw9FsGe4ECq' => '421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87'
        );

        foreach ($regular as $wif => $hex) {
            $private = PrivateKeyFactory::fromWif($wif);
            $this->assertInstanceOf($this->baseType, $private);
            $this->assertTrue($math->cmp($math->hexDec($hex), $private->getSecretMultiplier()) == 0);
            $this->assertFalse($private->isCompressed());
        }

        $compressed = array(
            'L3EQJoHJSXnCvNxiWBfoE7jKi89R9dcp1HPsdnVxRy6YGRmHoxKh' => 'b3615879ebf2a64542db64e29d87ae175479bafae275cdd3caf779507cac4f5b',
            'Kwn1Y1wcKUMjdPrVxBW8uVvuyq2B8EHFTKf7zGFc7J6ueaMvFUD8' => '109dac331c97d41c6be9db32a2c3fa848d1a637807f2ab5c0e009cfb8007d1a0',
            'KyvwuBYFruEssksxmDiQUKLwwtZt6WvFnPcdTnNPMddq15M3ezmU' => '50e36e410b227b70a1aa1abb28f1997aa6ec7a9ccddd4dc3ed708a18a0202b2f'
        );

        foreach ($compressed as $wif => $hex) {
            $private = PrivateKeyFactory::fromWif($wif);
            $this->assertInstanceOf($this->baseType, $private);
            $this->assertTrue($math->cmp($math->hexDec($hex), $private->getSecretMultiplier()) == 0);
            $this->assertTrue($private->isCompressed());
        }
    }

    /**
     * @expectedException \Afk11\Bitcoin\Exceptions\Base58ChecksumFailure
     */
    public function testInvalidWif()
    {
        PrivateKeyFactory::fromWif('50akdglashdgkjadsl');
    }
}
