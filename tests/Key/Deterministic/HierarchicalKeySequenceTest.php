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
        $this->assertEquals($eSeq, $sequence->fromNode($node));

        // canonicalize the sequence - library returns h.
        $eSeq = str_replace("'", 'h', $node);
        $this->assertEquals($eSeq, $sequence->getNode($sequence->fromNode($node)));
    }

    /**
     * @expectedException \LogicException
     */
    public function testHardenedSequenceFailure()
    {
        $sequence = new HierarchicalKeySequence();

        // Ensures that requesting a hardened sequence for >= 0x80000000 throws an exception
        $sequence->getHardened(HierarchicalKeySequence::START_HARDENED);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDecodePathFailure()
    {
        $sequence = new HierarchicalKeySequence();
        $sequence->decodePath('');
    }

    public function testDecodePath()
    {
        $sequence = new HierarchicalKeySequence();

        $expected = ['2147483648','2147483649','444','2147526030'];
        $this->assertEquals($expected, $sequence->decodePath("0'/1'/444/42382'"));
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
        $this->assertEquals($integer, implode("", $sequence->decodePath($node)));
    }

    public function getEncodePathVectors()
    {
        $array = ['2147483648','2147483649','444','2147526030'];
        $stdClass = new \stdClass();
        $stdClass->a = '2147483648';
        $stdClass->b = '2147483649';
        $stdClass->c = '444';
        $stdClass->d  = '2147526030';
        $traversable = \SplFixedArray::fromArray($array, false);

        return [
            [$array],
            [$stdClass],
            [$traversable]
        ];
    }

    /**
     * @dataProvider getEncodePathVectors
     */
    public function testEncodePathVectors($list)
    {
        $sequence = new HierarchicalKeySequence();

        $this->assertEquals("0h/1h/444/42382h", $sequence->encodePath($list));
    }
}
