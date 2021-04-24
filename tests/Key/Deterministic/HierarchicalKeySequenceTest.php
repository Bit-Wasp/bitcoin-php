<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\Deterministic;

use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeySequence;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class HierarchicalKeySequenceTest extends AbstractTestCase
{
    public function getSequenceVectors(): array
    {
        return [
            ['0', 0],
            ['0h', 2147483648],
            ["0'", 2147483648],
            ['1h', 2147483649],
            ['2147483647h', 4294967295],
        ];
    }

    /**
     * @dataProvider getSequenceVectors
     * @param string $node
     * @param int $eSeq
     */
    public function testGetSequence(string $node, int $eSeq)
    {
        $sequence = new HierarchicalKeySequence();
        $this->assertEquals([$eSeq], $sequence->decodeRelative($node));
    }

    public function testDecodePathFailure()
    {
        $sequence = new HierarchicalKeySequence();
        $this->expectException(\InvalidArgumentException::class);
        $sequence->decodeRelative('');
    }

    public function testDecodePath()
    {
        $sequence = new HierarchicalKeySequence();

        $decodedPath = $sequence->decodeRelative("0'/1'/444/42382'");
        $expected = [2147483648, 2147483649, 444, 2147526030];
        foreach ($decodedPath as $i => $item) {
            $this->assertIsInt($item);
            $this->assertEquals($expected[$i], $item);
        }
    }

    /**
     * @dataProvider getSequenceVectors
     * @param string $node
     * @param int $integer
     */
    public function testDecodePathVectors(string $node, int $integer)
    {
        $sequence = new HierarchicalKeySequence();

        // There should only be one, just implode to get the value
        $this->assertEquals($integer, implode("", $sequence->decodeRelative($node)));
    }
}
