<?php

namespace BitWasp\Bitcoin\Tests\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PublicKey;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class PublicKeyTest extends AbstractTestCase
{
    /**
     * @var PublicKey
     */
    protected $publicKey;

    /**
     * @var
     */
    protected $publicType = 'BitWasp\Bitcoin\Key\PublicKey';

    public function setUp()
    {
        $this->publicKey = null;
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testFromHex(EcAdapterInterface $ecAdapter)
    {
        $f    = file_get_contents(__DIR__.'/../Data/publickey.compressed.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $this->publicKey = PublicKeyFactory::fromHex($test->compressed, $ecAdapter);
            $this->assertInstanceOf($this->publicType, $this->publicKey);
            $this->assertSame($test->compressed, $this->publicKey->getBuffer()->serialize('hex'));
            $this->assertInstanceOf('\Mdanter\Ecc\PointInterface', $this->publicKey->getPoint());
            $this->assertSame($this->publicKey->getBuffer()->serialize('hex'), $test->compressed);
            $this->assertTrue($this->publicKey->isCompressed());
            $this->assertSame($this->publicKey->__toString(), $test->compressed);
        }
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testFromHexUncompressed(EcAdapterInterface $ecAdapter)
    {
        $f    = file_get_contents(__DIR__.'/../Data/publickey.compressed.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $this->publicKey = PublicKeyFactory::fromHex($test->uncompressed, $ecAdapter);
            $this->assertInstanceOf($this->publicType, $this->publicKey);
            $this->assertSame($test->uncompressed, $this->publicKey->getBuffer()->serialize('hex'));
            $this->assertInstanceOf('\Mdanter\Ecc\PointInterface', $this->publicKey->getPoint());
            $this->assertSame($this->publicKey->getBuffer()->serialize('hex'), $test->uncompressed);
            $this->assertSame($this->publicKey->__toString(), $test->uncompressed);
            $this->assertFalse($this->publicKey->isCompressed());

        }
    }

    /**
     *
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @expectedException \Exception
     */
    public function testFromHexInvalidLength(EcAdapterInterface $ecAdapter)
    {
        $hex = '02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44febaa';
        $this->publicKey = PublicKeyFactory::fromHex($hex, $ecAdapter);
        $this->assertInstanceOf($this->publicType, $this->publicKey);
        $this->assertSame($hex, $this->publicKey->getBuffer()->serialize('hex'));
    }

    /**
     * @expectedException \Exception
     */
    public function testFromHexInvalidByte()
    {
        $hex = '01cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb';
        $this->publicKey = PublicKeyFactory::fromHex($hex);
    }

    /**
     * @expectedException \Exception
     */
    public function testFromHexInvalidByte2()
    {
        $hex = '04cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb';
        $this->publicKey = PublicKeyFactory::fromHex($hex);
    }


    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testPubKeyHash(EcAdapterInterface $ecAdapter)
    {
        $f    = file_get_contents(__DIR__.'/../Data/publickey.pubkeyhash.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $pubkey = PublicKeyFactory::fromHex($test->key, $ecAdapter);
            $hash = $pubkey->getPubKeyHash();
            $this->assertSame($hash, $test->hash);
        }
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testSetCompressed(EcAdapterInterface $ecAdapter)
    {
        $f    = file_get_contents(__DIR__.'/../Data/publickey.compressed.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $pub = PublicKeyFactory::fromHex($test->uncompressed, $ecAdapter);
            $this->assertFalse($pub->isCompressed());
            $pub->setCompressed(true);
            $this->assertTrue($pub->isCompressed());
        }
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testSetUnCompressed(EcAdapterInterface $ecAdapter)
    {
        $f    = file_get_contents(__DIR__.'/../Data/publickey.compressed.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $pub = PublicKeyFactory::fromHex($test->compressed, $ecAdapter);
            $this->assertTrue($pub->isCompressed());
            $pub->setCompressed(false);
            $this->assertFalse($pub->isCompressed());
        }
    }

    public function testSerializeHex()
    {
        $f    = file_get_contents(__DIR__ . '/../Data/publickey.compressed.json');
        $json = json_decode($f);

        foreach ($json->test as $test) {
            $pubkey = PublicKeyFactory::fromHex($test->compressed);
            $hex    = $pubkey->getBuffer()->serialize('hex');
            $bin    = $pubkey->getBuffer()->serialize();

            for ($i = 0; $i < count($bin); $i++) {
                $nHex = bin2hex(substr($bin, $i, 1));
                $hHex = substr($hex, $i*2, 2);
                $this->assertSame($nHex, $hHex);
            }
        }
    }

    /**
     * @expectedException \Exception
     */
    public function testSetCompressedFailure()
    {
        $pub = PublicKeyFactory::fromHex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb');
        $pub->setCompressed('a');
    }
}
