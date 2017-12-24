<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Math;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class CompactIntegerTest extends AbstractTestCase
{
    public function getTestVectors()
    {
        $math = new Math;

        return [
            [
                $math,
                gmp_init('0'),
                gmp_init(0),
                false,
                false
            ],
            [
                $math,
                gmp_init(0x00123456),
                gmp_init(0),
                false,
                false
            ],
            [
                $math,
                gmp_init(0x01003456),
                gmp_init(0),
                false,
                false
            ],
            [
                $math,
                gmp_init(0x03000000),
                gmp_init(0),
                false,
                false
            ],
            [
                $math,
                gmp_init(0x04000000),
                gmp_init(0),
                false,
                false
            ],
            [
                $math,
                gmp_init(0x0923456),
                gmp_init(0),
                false,
                false
            ],
            [
                $math,
                gmp_init(0x01803456),
                gmp_init(0),
                false,
                false
            ],
            [
                $math,
                gmp_init(0x02800056),
                gmp_init(0),
                false,
                false
            ],
            [
                $math,
                gmp_init(0x03800000),
                gmp_init(0),
                false,
                false
            ],
            [
                $math,
                gmp_init(0x04800000),
                gmp_init(0),
                false,
                false
            ],
            [
                $math,
                gmp_init(0x01123456),
                gmp_init(0x01120000),
                false,
                false
            ],
            [
                $math,
                gmp_init(0x01fedcba),
                gmp_init(0x01fe0000),
                true,
                false
            ],
            [
                $math,
                gmp_init(0x02123456),
                gmp_init(0x02123400),
                false,
                false
            ],
            [
                $math,
                gmp_init(0x03123456),
                gmp_init(0x03123456),
                false,
                false
            ],
            [
                $math,
                gmp_init(0x04123456),
                gmp_init(0x04123456),
                false,
                false
            ],
            [
                $math,
                gmp_init(0x04923456),
                gmp_init(0x04923456),
                true,
                false
            ],
            [
                $math,
                gmp_init(0x05009234),
                gmp_init(0x05009234),
                false,
                false
            ],
            [
                $math,
                gmp_init(0x20123456),
                gmp_init(0x20123456),
                false,
                false
            ]
        ];
    }

    /**
     * @param Math $math
     * @param \GMP $int
     * @param \GMP $eInt
     * @param bool $eNegative
     * @param bool $eOverflow
     * @dataProvider getTestVectors
     */
    public function testCases(Math $math, \GMP $int, \GMP $eInt, bool $eNegative, bool $eOverflow)
    {
        $negative = false;
        $overflow = false;
        $integer = $math->decodeCompact(gmp_strval($int, 10), $negative, $overflow);
        $compact = $math->encodeCompact($integer, $eNegative);
        $this->assertTrue(gmp_cmp($eInt, $compact) === 0);
        $this->assertEquals($eNegative, $negative);
        $this->assertEquals($eOverflow, $overflow);
    }

    public function testOverflow()
    {
        $math = new Math();
        $negative = false;
        $overflow = false;
        $math->decodeCompact($math->hexDec('0xff123456'), $negative, $overflow);
        $this->assertEquals(false, $negative);
        $this->assertEquals(true, $overflow);
    }
}
