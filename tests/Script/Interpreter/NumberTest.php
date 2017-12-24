<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script\Interpreter;

use BitWasp\Bitcoin\Script\Interpreter\Number;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class NumberTest extends AbstractTestCase
{
    /**
     * @return array
     */
    public function getVectors()
    {
        return [
            [0, 0, ''],
            [1, 1, '01'],
            [-1, 1, '81'], //[255, 2, 'ff00'],
            [127, 1, '7f'],
            [-127, 1, 'ff'],
            [255, 2, 'ff00'],
            [256, 2, '0001'],
            [-256, 2, '0081'],
            [-255, 2, 'ff80'],
            [-pow(2, 31)+1, 4, 'ffffffff'],
            [pow(2, 31)-1, 4, 'ffffff7f'],
        ];
    }

    /**
     * @param int|string $int
     * @param int $expectedSize
     * @param string $expectedHex
     * @dataProvider getVectors
     */
    public function testInts(int $int, int $expectedSize, string $expectedHex)
    {
        $number = Number::int($int);
        $buffer = $number->getBuffer();
        $this->assertEquals($expectedSize, $buffer->getSize());
        $this->assertEquals($expectedHex, $buffer->getHex());
    }

    /**
     * @throws \Exception
     * @param int|string $int
     * @param int $expectedSize
     * @param string $expectedHex
     * @dataProvider getVectors
     */
    public function testVector(int $int, int $expectedSize, string $expectedHex)
    {
        $buffer = Buffer::hex($expectedHex);
        $number = Number::buffer($buffer, false);

        $this->assertEquals($expectedSize, $buffer->getSize());
        $this->assertEquals($int, $number->getInt());
    }
}
