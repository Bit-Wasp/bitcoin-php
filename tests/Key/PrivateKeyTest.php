<?php

namespace BitWasp\Bitcoin\Tests\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PrivateKey;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use Mdanter\Ecc\Primitives\GeneratorPoint;

class PrivateKeyTest extends AbstractTestCase
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
    protected $baseType = 'BitWasp\Bitcoin\Key\PrivateKey';

    /**
     * @var string
     */
    protected $publicType = 'BitWasp\Bitcoin\Key\PublicKey';

    /**
     *
     */
    public function setUp()
    {
        $this->math = $this->safeMath();
        $this->generator = $this->safeGenerator();
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testCreatePrivateKey(EcAdapterInterface $ecAdapter)
    {
        $hex = '4141414141414141414141414141414141414141414141414141414141414141';
        $key = $ecAdapter->getMath()->hexDec($hex);
        $privateKey = PrivateKeyFactory::fromInt($key, false, $ecAdapter);

        $this->assertEquals($key, $privateKey->getInt());
        $this->assertSame($privateKey->getBuffer()->getHex(), '4141414141414141414141414141414141414141414141414141414141414141');
        $this->assertFalse($privateKey->isCompressed());
        $this->assertTrue($privateKey->isPrivate());
        $this->assertSame(
            '04eec7245d6b7d2ccb30380bfbe2a3648cd7a942653f5aa340edcea1f2836866198bd9fc8678e246f23f40bfe8d928d3f37a51642aed1d5b471a1a0db4f71891ea',
            $privateKey->getPublicKey()->getBuffer()->getHex()
        );
    }

    /**
     * @dataProvider getEcAdapters
     * @expectedException \Exception
     * @param EcAdapterInterface $ecAdapter
     */
    public function testCreatePrivateKeyFailure(EcAdapterInterface $ecAdapter)
    {
        $dec = $ecAdapter->getMath()->hexDec('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141');
        PrivateKeyFactory::fromInt($dec, $ecAdapter);
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testGenerateNewUncompressed(EcAdapterInterface $ecAdapter)
    {
        $privateKey = PrivateKeyFactory::create(false, $ecAdapter);
        $this->assertFalse($privateKey->isCompressed());
        $this->assertTrue($privateKey->isPrivate());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testIsCompressed(EcAdapterInterface $ecAdapter)
    {
        $hex = '4141414141414141414141414141414141414141414141414141414141414141';
        $privateKey = PrivateKeyFactory::fromHex($hex, false, $ecAdapter);
        $this->assertFalse($privateKey->isCompressed());

        $privateKey = PrivateKeyFactory::fromHex($hex, true, $ecAdapter);
        $this->assertTrue($privateKey->isCompressed());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testGenerateNewCompressed(EcAdapterInterface $ecAdapter)
    {
        $privateKey = PrivateKeyFactory::create(true, $ecAdapter);
        $this->assertTrue($privateKey->isCompressed());
        $this->assertTrue($privateKey->isPrivate());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testGetWif(EcAdapterInterface $ecAdapter)
    {
        $privateKey = PrivateKeyFactory::fromHex('4141414141414141414141414141414141414141414141414141414141414141', false, $ecAdapter);
        $network = NetworkFactory::bitcoin();

        $this->assertSame($privateKey->toWif($network), '5JK2Rv7ZquC9J11AQZXXU7M9S17z193GPjsKPU3gSANJszAW3dU');
        $this->assertSame($privateKey->toWif(), '5JK2Rv7ZquC9J11AQZXXU7M9S17z193GPjsKPU3gSANJszAW3dU');

        $privateKey = PrivateKeyFactory::fromHex('4141414141414141414141414141414141414141414141414141414141414141', true, $ecAdapter);
        $this->assertSame($privateKey->toWif($network), 'KyQZJyRyxqNBc31iWzZjUf1vDMXpbcUzwND6AANq44M3v38smDkA');
        $this->assertSame($privateKey->toWif(), 'KyQZJyRyxqNBc31iWzZjUf1vDMXpbcUzwND6AANq44M3v38smDkA');
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testGetPubKeyHash(EcAdapterInterface $ecAdapter)
    {
        $privateKey = PrivateKeyFactory::fromHex('4141414141414141414141414141414141414141414141414141414141414141', false, $ecAdapter);
        $this->assertSame('d00baafc1c7f120ab2ae0aa22160b516cfcf9cfe', $privateKey->getPubKeyHash()->getHex());

        $privateKey = PrivateKeyFactory::fromHex('4141414141414141414141414141414141414141414141414141414141414141', true, $ecAdapter);
        $this->assertSame('c53c82d3357f1f299330d585907b7c64b6b7a5f0', $privateKey->getPubKeyHash()->getHex());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testSerialize(EcAdapterInterface $ecAdapter)
    {
        $privateKey = PrivateKeyFactory::fromHex('4141414141414141414141414141414141414141414141414141414141414141', false, $ecAdapter);
        $this->assertSame('4141414141414141414141414141414141414141414141414141414141414141', $privateKey->getBuffer()->getHex());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testFromWif(EcAdapterInterface $ecAdapter)
    {
        $math = $ecAdapter->getMath();
        $regular = array(
            '5KeNtJ66K7UNpirG3574f9Z8SjPDPTc5YaSBczttdoqNdQMK5b9' => 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b',
            '5J6B9UWZSxwHuJF3jv1zi2ZxMAVhA7bBvFFcZXFo7ga1UdgNtDs' => '2413fb3709b05939f04cf2e92f7d0897fc2596f9ad0b8a9ea855c7bfebaae892',
            '5JKQJXqLFxQ9JSw2Wc4Z5ZY1v1BR8u4BfndtXZd1Kw9FsGe4ECq' => '421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87'
        );

        foreach ($regular as $wif => $hex) {
            $private = PrivateKeyFactory::fromWif($wif, $ecAdapter);
            $this->assertTrue($math->cmp($math->hexDec($hex), $private->getSecretMultiplier()) == 0);
            $this->assertFalse($private->isCompressed());
        }

        $compressed = array(
            'L3EQJoHJSXnCvNxiWBfoE7jKi89R9dcp1HPsdnVxRy6YGRmHoxKh' => 'b3615879ebf2a64542db64e29d87ae175479bafae275cdd3caf779507cac4f5b',
            'Kwn1Y1wcKUMjdPrVxBW8uVvuyq2B8EHFTKf7zGFc7J6ueaMvFUD8' => '109dac331c97d41c6be9db32a2c3fa848d1a637807f2ab5c0e009cfb8007d1a0',
            'KyvwuBYFruEssksxmDiQUKLwwtZt6WvFnPcdTnNPMddq15M3ezmU' => '50e36e410b227b70a1aa1abb28f1997aa6ec7a9ccddd4dc3ed708a18a0202b2f'
        );

        foreach ($compressed as $wif => $hex) {
            $private = PrivateKeyFactory::fromWif($wif, $ecAdapter);
            $this->assertTrue($math->cmp($math->hexDec($hex), $private->getSecretMultiplier()) == 0);
            $this->assertTrue($private->isCompressed());
        }
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
     */
    public function testInvalidWif()
    {
        PrivateKeyFactory::fromWif('50akdglashdgkjadsl');
    }
}
