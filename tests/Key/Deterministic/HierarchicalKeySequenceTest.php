<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\Deterministic;

use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeySequence;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class HierarchicalKeySequenceTest extends AbstractTestCase
{
    public function getSequenceVectors()
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
     * @param $node
     * @param $eSeq
     */
    public function testGetSequence($node, $eSeq)
    {
        $sequence = new HierarchicalKeySequence();
        $this->assertEquals([$eSeq], $sequence->decodeRelative($node));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDecodePathFailure()
    {
        $sequence = new HierarchicalKeySequence();
        $sequence->decodeRelative('');
    }

    public function testDecodePath()
    {
        $sequence = new HierarchicalKeySequence();

        $expected = ['2147483648','2147483649','444','2147526030'];
        $this->assertEquals($expected, $sequence->decodeRelative("0'/1'/444/42382'"));
    }

    /**
     * @dataProvider getSequenceVectors
     * @param $node
     * @param $integer
     */
    public function testDecodePathVectors($node, $integer)
    {
        $sequence = new HierarchicalKeySequence();

        // There should only be one, just implode to get the value
        $this->assertEquals($integer, implode("", $sequence->decodeRelative($node)));
    }
}
