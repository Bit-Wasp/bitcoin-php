<?php

namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputCollection;

class TransactionOutputCollectionTest extends AbstractTestCase
{
    /**
     * @expectedException \OutOfRangeException
     */
    public function testOutOfRange()
    {
        $collection = new TransactionOutputCollection();
        $collection->getOutput(10);
    }

    public function testSlice()
    {
        $in0 = new TransactionOutput(50, new Script());
        $in1 = new TransactionOutput(50, new Script());
        $arr = [$in0, $in1];
        $collection = new TransactionOutputCollection($arr);
        $this->assertEquals(2, count($collection));
        $this->assertEquals(1, count($collection->slice(0, 1)));
        $this->assertSame($in0, $collection->getOutput(0));
        $this->assertEquals($arr, $collection->getOutputs());
    }
}
