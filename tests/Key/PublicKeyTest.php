<?php

namespace BitWasp\Bitcoin\Tests\Key;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PublicKey;
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
            $this->assertSame($test->compressed, $this->publicKey->getBuffer()->getHex());
            $this->assertInstanceOf('\Mdanter\Ecc\PointInterface', $this->publicKey->getPoint());
            $this->assertSame($this->publicKey->getBuffer()->getHex(), $test->compressed);
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
            $this->assertSame($test->uncompressed, $this->publicKey->getBuffer()->getHex());
            $this->assertInstanceOf('\Mdanter\Ecc\PointInterface', $this->publicKey->getPoint());
            $this->assertSame($this->publicKey->getBuffer()->getHex(), $test->uncompressed);
            $this->assertSame($this->publicKey->__toString(), $test->uncompressed);
            $this->assertFalse($this->publicKey->isCompressed());
            $this->assertFalse($this->publicKey->isPrivate());

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
        $this->assertSame($hex, $this->publicKey->getBuffer()->getHex());
    }

    /**
     * @expectedException \Exception
     */
    public function testFromHexInvalidByte()
    {
        $hex = '01cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb';
        $this->publicKey = PublicKeyFactory::fromHex($hex);
    }

    public function testIsCompressedOrUncompressed()
    {
        $this->assertFalse(PublicKey::isCompressedOrUncompressed(Buffer::hex('00')));
        $this->assertTrue (PublicKey::isCompressedOrUncompressed(Buffer::hex('0400010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203')));
        $this->assertFalse(PublicKey::isCompressedOrUncompressed(Buffer::hex('0400010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900')));
        $this->assertFalse(PublicKey::isCompressedOrUncompressed(Buffer::hex('040001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304')));

        $this->assertTrue (PublicKey::isCompressedOrUncompressed(Buffer::hex('020001020304050607080900010203040506070809000102030405060708090001')));
        $this->assertTrue (PublicKey::isCompressedOrUncompressed(Buffer::hex('030001020304050607080900010203040506070809000102030405060708090001')));
        $this->assertFalse(PublicKey::isCompressedOrUncompressed(Buffer::hex('03000102030405060708090001020304050607080900010203040506070809000102')));
        $this->assertFalse(PublicKey::isCompressedOrUncompressed(Buffer::hex('0300010203040506070809000102030405060708090001020304050607080900')));

        $this->assertFalse(PublicKey::isCompressedOrUncompressed(Buffer::hex('050001020304050607080900010203040506070809000102030405060708090001')));

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
            $this->assertSame(
                $test->hash,
                PublicKeyFactory::fromHex($test->key, $ecAdapter)
                    ->getPubKeyHash()
                    ->getHex()
            );
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
            $hex    = $pubkey->getBuffer()->getHex();
            $bin    = $pubkey->getBuffer()->getBinary();

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
