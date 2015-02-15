<?php

namespace Afk11\Bitcoin\Tests\Util;

use Afk11\Bitcoin\Base58;

class Base58Test extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Afk11\Bitcoin\Base58
     */
    protected $base58;

    public function setUp()
    {
        $this->base58 = new \Afk11\Bitcoin\Base58();
    }

    /**
     * Test results of encoding a hex string against test vectors
     */
    public function testEncode()
    {
        $f    = file_get_contents(__DIR__.'/Data/base58.encodedecode.json');
        $json = json_decode($f);

        foreach ($json->test as $test) {
            $hash = $this->base58->encode($test[0]);
            $this->assertSame($test[1], $hash);
        }
    }

    /**
     * Test that uneven length strings will throw an exception
     * @expectedException \Exception
     */
    public function testEncodeWithException()
    {
        $hash = $this->base58->encode('41414141a');
    }

    /**
     * Test that encoding and decoding a string results in the original data
     */
    public function testEncodeDecode()
    {
        $f    = file_get_contents(__DIR__.'/Data/base58.encodedecode.json');
        $json = json_decode($f);

        foreach ($json->test as $test) {
            $encoded = $this->base58->encode($test[0]);
            $this->assertSame($test[1], $encoded);

            $back    = $this->base58->decode($encoded);
            $this->assertSame($test[0], $back);
        }
    }

    /**
     * Test the application of padding 1's when 00 bytes are found.
     * Satoshism.
     */
    public function testWeird()
    {
        $str    = '00000000000000000000';
        $encode = $this->base58->encode($str);
        $decode = $this->base58->decode($encode);

        $this->assertSame($encode, '1111111111');
        $this->assertSame($decode, $str);

    }

    /**
     * Check that when data is encoded with a checksum, that we can decode
     * correctly and
     */
    public function testEncodeDecodeCheck()
    {
        $f     = file_get_contents(__DIR__ . '/Data/base58.encodedecode.json');
        $json  = json_decode($f);

        foreach ($json->test as $test) {
            $encoded = $this->base58->encodeCheck($test[0]);
            $back    = $this->base58->decodeCheck($encoded);

            $this->assertSame($test[0], $back);
        }
    }

    /**
     * @expectedException \Afk11\Bitcoin\Exceptions\Base58ChecksumFailure
     */
    public function testDecodeCheckChecksumFailure()
    {
        // Base58Check encoded data has a checksum at the end.

        // 12D2adLM3UKy4bH891ZFDkWmXmotrMoF <-- valid
        // 12D2adLM3UKy4cH891ZFDkWmXmotrMoF <-- has typo, b replaced with c.
        //              ^

        \Afk11\Bitcoin\Base58::decodeCheck('12D2adLM3UKy4cH891ZFDkWmXmotrMoF');

    }
}
