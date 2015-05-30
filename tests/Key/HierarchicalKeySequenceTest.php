<?php

namespace BitWasp\Bitcoin\Tests\Key;


use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeySequence;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class HierarchicalKeySequenceTest extends AbstractTestCase
{
    public function getSequenceVectors()
    {
        return [
            ["0", '0'],
            ["0h", '2147483648'],
            ["0'", '2147483648'],
            ["1h", '2147483649'],
            ["2147483647h", '4294967295'],
        ];
    }

    /**
     * @dataProvider getSequenceVectors
     * @param $node
     * @param $eSeq
     */
    public function testGetSequence($node, $eSeq)
    {
        $sequence = new HierarchicalKeySequence(new Math());
        $this->assertEquals($eSeq, $sequence->fromNode($node));

        // canonicalize the sequence - library returns h.
        $eSeq = str_replace("'", "h", $node);
        $this->assertEquals($eSeq, $sequence->getNode($sequence->fromNode($node)));
    }

    /**
     * @expectedException \LogicException
     */
    public function testHardenedSequenceFailure()
    {
        $sequence = new \BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeySequence(new Math());

        // Ensures that requesting a hardened sequence for >= 0x80000000 throws an exception
        $sequence->getHardened(HierarchicalKeySequence::START_HARDENED);
    }
}