<?php

namespace BitWasp\Bitcoin\Tests;

use BitWasp\Bitcoin\Base58;
use BitWasp\Buffertools\Buffer;

class Base58Test extends \PHPUnit_Framework_TestCase
{

    public function getVectors()
    {
        $f = file_get_contents(__DIR__ . '/Data/base58.encodedecode.json');
        $json = json_decode($f);

        $results = [];
        foreach ($json->test as $test) {
            $buffer = Buffer::hex($test[0]);
            $base58 = $test[1];
            $results[] = [$buffer, $base58];
        }

        return $results;
    }
    
    /**
     * Test that encoding and decoding a string results in the original data
     * @dataProvider getVectors
     */
    public function testEncodeDecode(Buffer $bs, $base58)
    {
        $encoded = Base58::encode($bs);
        $this->assertEquals($base58, $encoded);

        $decoded = Base58::decode($encoded)->getHex();
        $this->assertEquals($bs->getHex(), $decoded);

    }

    /**
     * Test the application of padding 1's when 00 bytes are found.
     * Satoshism.
     */
    public function testWeird()
    {
        $bs = Buffer::hex('00000000000000000000');
        $b58 = Base58::encode($bs);
        $this->assertSame($b58, '1111111111');
        $this->assertEquals($bs, Base58::decode($b58));
    }

    /**
     * Check that when data is encoded with a checksum, that we can decode
     * correctly
     * @dataProvider getVectors
     */
    public function testEncodeDecodeCheck(Buffer $bs, $base58)
    {
        $encoded = Base58::encodeCheck($bs);
        $this->assertTrue($bs->equals(Base58::decodeCheck($encoded)));
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
     */
    public function testDecodeCheckChecksumFailure()
    {
        // Base58Check encoded data has a checksum at the end.
        // 12D2adLM3UKy4bH891ZFDkWmXmotrMoF <-- valid
        // 12D2adLM3UKy4cH891ZFDkWmXmotrMoF <-- has typo, b replaced with c.
        //              ^

        Base58::decodeCheck('12D2adLM3UKy4cH891ZFDkWmXmotrMoF');

    }
}
