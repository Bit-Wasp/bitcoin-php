<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests;

use BitWasp\Bitcoin\Base58;
use BitWasp\Buffertools\Buffer;

class Base58Test extends AbstractTestCase
{

    public function getVectors()
    {
        $json = json_decode($this->dataFile('base58.encodedecode.json'));

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
     * @param Buffer $bs
     * @param string $base58
     */
    public function testEncodeDecode(Buffer $bs, string $base58)
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
     * @param Buffer $bs
     * @param string $base58
     */
    public function testEncodeDecodeCheck(Buffer $bs, string $base58)
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

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\Base58InvalidCharacter
     */
    public function testDecodeBadCharacter()
    {
        // 12D2adLM3UKy4bH891ZFDkWmXmotrMoF <-- valid
        // 12D2adLM3UKy4bH891ZFDkWmXmotrM0F <-- 0 is not allowed in base58 strings
        //                               ^

        Base58::decode('12D2adLM3UKy4cH891ZFDkWmXmotrM0F');
    }
}
