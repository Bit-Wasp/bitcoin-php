<?php

namespace BitWasp\Bitcoin\Tests\Key;

use BitWasp\Bitcoin\Key\Point;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PublicKey;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class PublicKeyTest extends AbstractTestCase
{

    /**
     * @var
     */
    protected $publicType = 'BitWasp\Bitcoin\Key\PublicKey';

    public function getPublicVectors()
    {
        $f = file_get_contents(__DIR__.'/../Data/publickey.compressed.json');
        $json = json_decode($f);
        $results = [];
        foreach ($json->test as $test) {
            foreach ($this->getEcAdapters() as $adapter) {
                $results[] = [
                    $adapter[0],
                    $test->compressed,
                    $test->uncompressed
                ];
            }
        }

        return $results;
    }

    /**
     * @dataProvider getPublicVectors
     * @param EcAdapterInterface $ecAdapter
     * @param string $eCompressed
     * @param string $eUncompressed
     */
    public function testFromHex(EcAdapterInterface $ecAdapter, $eCompressed, $eUncompressed)
    {
        unset($eUncompressed);
        $publicKey = PublicKeyFactory::fromHex($eCompressed, $ecAdapter);

        $this->assertInstanceOf($this->publicType, $publicKey);
        $this->assertInstanceOf('\Mdanter\Ecc\Primitives\PointInterface', $publicKey->getPoint());
        $this->assertSame($eCompressed, $publicKey->getBuffer()->getHex());
        $this->assertSame($publicKey->getBuffer()->getHex(), $eCompressed);
        $this->assertTrue($publicKey->isCompressed());
    }

    /**
     * @dataProvider getPublicVectors
     * @param EcAdapterInterface $ecAdapter
     */
    public function testFromHexUncompressed(EcAdapterInterface $ecAdapter, $eCompressed, $eUncompressed)
    {
        unset($eCompressed);
        $publicKey = PublicKeyFactory::fromHex($eUncompressed, $ecAdapter);
        
        $this->assertInstanceOf($this->publicType, $publicKey);
        $this->assertInstanceOf('\Mdanter\Ecc\Primitives\PointInterface', $publicKey->getPoint());
        $this->assertSame($eUncompressed, $publicKey->getBuffer()->getHex());
        $this->assertSame($publicKey->getBuffer()->getHex(), $eUncompressed);
        $this->assertFalse($publicKey->isCompressed());
        $this->assertFalse($publicKey->isPrivate());
        
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @expectedException \Exception
     */
    public function testFromHexInvalidLength(EcAdapterInterface $ecAdapter)
    {
        $hex = '02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44febaa';
        $publicKey = PublicKeyFactory::fromHex($hex, $ecAdapter);
        $this->assertInstanceOf($this->publicType, $publicKey);
        $this->assertSame($hex, $publicKey->getBuffer()->getHex());
    }

    /**
     * @expectedException \Exception
     */
    public function testFromHexInvalidByte()
    {
        $hex = '01cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb';
        PublicKeyFactory::fromHex($hex);
    }

    public function testIsCompressedOrUncompressed()
    {
        $this->assertFalse(PublicKey::isCompressedOrUncompressed(Buffer::hex('00')));
        $this->assertTrue(PublicKey::isCompressedOrUncompressed(Buffer::hex('0400010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203')));
        $this->assertFalse(PublicKey::isCompressedOrUncompressed(Buffer::hex('0400010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900')));
        $this->assertFalse(PublicKey::isCompressedOrUncompressed(Buffer::hex('040001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304')));

        $this->assertTrue(PublicKey::isCompressedOrUncompressed(Buffer::hex('020001020304050607080900010203040506070809000102030405060708090001')));
        $this->assertTrue(PublicKey::isCompressedOrUncompressed(Buffer::hex('030001020304050607080900010203040506070809000102030405060708090001')));
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
        PublicKeyFactory::fromHex($hex);
    }

    public function getPkHashVectors()
    {
        $f = file_get_contents(__DIR__.'/../Data/publickey.pubkeyhash.json');
        $json = json_decode($f);
        $results = [];

        foreach ($json->test as $test) {
            $results[] = [
                $test->key,
                $test->hash
            ];
        }
        
        return $results;
    }

    /**
     * @dataProvider getPkHashVectors
     * @param EcAdapterInterface $ecAdapter
     * @param string $eKey - hex public key
     * @param string $eHash - hex sha256ripemd160 of public key
     */
    public function testPubKeyHash($eKey, $eHash)
    {
        $this->assertSame(
            $eHash,
            PublicKeyFactory::fromHex($eKey)
                ->getPubKeyHash()
                ->getHex()
        );
    }

    /**
     * @dataProvider getPublicVectors
     * @param EcAdapterInterface $ecAdapter
     */
    public function testSetCompressed(EcAdapterInterface $ecAdapter, $eCompressed, $eUncompressed)
    {
        unset($eCompressed);
        $pub = PublicKeyFactory::fromHex($eUncompressed, $ecAdapter);
        $this->assertFalse($pub->isCompressed());

        $pub->setCompressed(true);
        $this->assertTrue($pub->isCompressed());
    }

    /**
     * @dataProvider getPublicVectors
     * @param EcAdapterInterface $ecAdapter
     */
    public function testSetUnCompressed(EcAdapterInterface $ecAdapter, $eCompressed, $eUncompressed)
    {
        unset($eUncompressed);
        
        $pub = PublicKeyFactory::fromHex($eCompressed, $ecAdapter);
        $this->assertTrue($pub->isCompressed());

        $pub->setCompressed(false);
        $this->assertFalse($pub->isCompressed());
    }

    /**
     * @dataProvider getPublicVectors
     */
    public function testSerializeHex(EcAdapterInterface $ecAdapter, $eCompressed, $eUncompressed)
    {
        unset($eUncompressed);

        $pubkey = PublicKeyFactory::fromHex($eCompressed, $ecAdapter);
        $hex = $pubkey->getBuffer()->getHex();
        $bin = $pubkey->getBuffer()->getBinary();

        for ($i = 0; $i < count($bin); $i++) {
            $nHex = bin2hex(substr($bin, $i, 1));
            $hHex = substr($hex, $i*2, 2);
            $this->assertSame($nHex, $hHex);
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

    public function testPublicKeyFromPoint(EcAdapterInterface $ecAdapter)
    {
        $point = new Point(
            $ecAdapter->getMath(),
            $ecAdapter->getGenerator(),
            '94075108042016923119479678483338406049382274483038030215794449747077048324075',
            '68068239036272628750825525318805297439390570305050728515552223656985804538350'
        );

        $publicKey = PublicKeyFactory::fromPoint($point);
        $this->assertSame($point, $publicKey->getPoint());
    }
}
