<?php

namespace Afk11\Bitcoin\Tests\Key;

use Afk11\Bitcoin\Key\PublicKey;
use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Key\PublicKeyFactory;

class PublicKeyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PublicKey
     */
    protected $publicKey;

    protected $publicType;

    public function setUp()
    {
        $this->publicKey = null;
        $this->publicType = 'Afk11\Bitcoin\Key\PublicKey';
    }

    public function testFromHex()
    {
        $f    = file_get_contents(__DIR__.'/../Data/publickey.compressed.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $this->publicKey = PublicKeyFactory::fromHex($test->compressed);
            $this->assertInstanceOf($this->publicType, $this->publicKey);
            $this->assertSame($test->compressed, $this->publicKey->serialize('hex'));
            $this->assertInstanceOf('\Mdanter\Ecc\PointInterface', $this->publicKey->getPoint());
            $this->assertSame($this->publicKey->getBuffer(), $test->compressed);
            $this->assertTrue($this->publicKey->isCompressed());
            $this->assertSame($this->publicKey->__toString(), $test->compressed);
            $this->assertSame(33, $this->publicKey->getSize());
            $this->assertSame(66, $this->publicKey->getSize('hex'));
        }
    }

    public function testFromHexUncompressed()
    {
        $f    = file_get_contents(__DIR__.'/../Data/publickey.compressed.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $this->publicKey = PublicKeyFactory::fromHex($test->uncompressed);
            $this->assertInstanceOf($this->publicType, $this->publicKey);
            $this->assertSame($test->uncompressed, $this->publicKey->serialize('hex'));
            $this->assertInstanceOf('\Mdanter\Ecc\PointInterface', $this->publicKey->getPoint());
            $this->assertSame($this->publicKey->getBuffer(), $test->uncompressed);
            $this->assertSame($this->publicKey->__toString(), $test->uncompressed);
            $this->assertFalse($this->publicKey->isCompressed());
            $this->assertSame(65, $this->publicKey->getSize());
            $this->assertSame(130, $this->publicKey->getSize('hex'));
        }
    }

    /**
     * @expectedException \Exception
     */
    public function testFromHexInvalidLength()
    {
        $hex = '02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44febaa';
        $this->publicKey = PublicKeyFactory::fromHex($hex);
        $this->assertInstanceOf($this->publicType, $this->publicKey);
        $this->assertSame($hex, $this->publicKey->serialize('hex'));
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

    public function testRecoverYfromX()
    {
        $f = file_get_contents(__DIR__.'/../Data/publickey.compressed.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $byte = substr($test->compressed, 0, 2);
            $x    = Bitcoin::getMath()->hexDec(substr($test->compressed, 2, 64));
            $realy= Bitcoin::getMath()->hexDec(substr($test->uncompressed, 66, 64));
            $y    = PublicKey::recoverYfromX($x, $byte);
            $this->assertSame($realy, $y);
        }

    }

    /**
     * @expectedException \Exception
     */
    public function testRecoverYfromXException()
    {
        $x = 0;
        PublicKey::recoverYfromX($x, '02');
    }

    public function testCompressKeys()
    {
        $f    = file_get_contents(__DIR__.'/../Data/publickey.compressed.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $key        = PublicKeyFactory::fromHex($test->uncompressed);
            $compressed = PublicKey::compress($key);
            $this->assertSame($compressed, $test->compressed);
        }
    }

    public function testCompressPoint()
    {
        $hex             = '02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb';
        $this->publicKey = PublicKeyFactory::fromHex($hex);
        $point           = $this->publicKey->getPoint();
        $compressed      = PublicKey::compress($point);
        $this->assertSame($hex, $compressed);
    }

    /**
     * @expectedException \Exception
     */
    public function testCompressPointException()
    {
        PublicKey::compress('a');
    }

    public function testPubKeyHash()
    {
        $f    = file_get_contents(__DIR__.'/../Data/publickey.pubkeyhash.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $pubkey = PublicKeyFactory::fromHex($test->key);
            $hash   = $pubkey->getPubKeyHash();
            $this->assertSame($hash, $test->hash);
        }
    }

    public function testSetCompressed()
    {
        $f    = file_get_contents(__DIR__.'/../Data/publickey.compressed.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $pub = PublicKeyFactory::fromHex($test->uncompressed);
            $this->assertFalse($pub->isCompressed());
            $pub->setCompressed(true);
            $this->assertTrue($pub->isCompressed());
        }
    }

    public function testSetUnCompressed()
    {
        $f    = file_get_contents(__DIR__.'/../Data/publickey.compressed.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $pub = PublicKeyFactory::fromHex($test->compressed);
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
            $hex    = $pubkey->serialize('hex');
            $bin    = $pubkey->serialize();

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
