<?php

namespace BitWasp\Bitcoin\Tests\Math;


use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Math\CompactInteger;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class CompactIntegerTest extends AbstractTestCase
{
    public function getTestVectors()
    {
        $math = new Math();
        $compactInteger = new CompactInteger($math);

        return [
            [
                $compactInteger,
                '0',
                '0',
                false,
                false
            ],
            [
                $compactInteger,
                0x00123456,
                '0',
                false,
                false
            ],
            [
                $compactInteger,
                0x01003456,
                '0',
                false,
                false
            ],
            [
                $compactInteger,
                0x03000000,
                '0',
                false,
                false
            ],
            [
                $compactInteger,
                0x04000000,
                '0',
                false,
                false
            ],
            [
                $compactInteger,
                0x0923456,
                '0',
                false,
                false
            ],
            [
                $compactInteger,
                0x01803456,
                '0',
                false,
                false
            ],
            [
                $compactInteger,
                0x02800056,
                '0',
                false,
                false
            ],
            [
                $compactInteger,
                0x03800000,
                '0',
                false,
                false
            ],
            [
                $compactInteger,
                $math->hexDec('0x04800000'),
                '0',
                false,
                false
            ],
            [
                $compactInteger,
                $math->hexDec('0x01123456'),
                $math->hexDec('0x01120000'),
                false,
                false
            ],
            [
                $compactInteger,
                $math->hexDec('0x01fedcba'),
                $math->hexDec('01fe0000'),
                true,
                false
            ],
            [
                $compactInteger,
                $math->hexDec('0x02123456'),
                $math->hexDec('0x02123400'),
                false,
                false
            ],
            [
                $compactInteger,
                $math->hexDec('0x03123456'),
                $math->hexDec('0x03123456'),
                false,
                false
            ],
            [
                $compactInteger,
                $math->hexDec('0x04123456'),
                $math->hexDec('0x04123456'),
                false,
                false
            ],
            [
                $compactInteger,
                $math->hexDec('04923456'),
                $math->hexDec('04923456'),
                true,
                false
            ],
            [
                $compactInteger,
                $math->hexDec('0x05009234'),
                $math->hexDec('0x05009234'),
                false,
                false
            ],
            [
                $compactInteger,
                $math->hexDec('0x20123456'),
                $math->hexDec('0x20123456'),
                false,
                false
            ]
        ];
    }

    /**
     * @param CompactInteger $compactInt
     * @param int|string $int
     * @param int|string $eInt
     * @param bool $eNegative
     * @param bool $eOverflow
     * @dataProvider getTestVectors
     */
    public function testCases(CompactInteger $compactInt, $int, $eInt, $eNegative, $eOverflow)
    {
        $negative = false;
        $overflow = false;
        $integer = $compactInt->set($int, $negative, $overflow);
        $compact = $compactInt->read($integer, $eNegative);
        $this->assertEquals($eInt, $compact);
        $this->assertEquals($eNegative, $negative);
        $this->assertEquals($eOverflow, $overflow);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFNegative()
    {
        $math = new Math();
        $compact = new CompactInteger($math);
        $compact->read(1, 1);
    }

    public function testOverflow()
    {
        $math = new Math();
        $compact = new CompactInteger($math);
        $negative = false;
        $overflow = false;
        $compact->set($math->hexDec('0xff123456'), $negative, $overflow);
        $this->assertEquals(false, $negative);
        $this->assertEquals(true, $overflow);
    }
}
