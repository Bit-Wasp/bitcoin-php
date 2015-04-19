<?php

namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInputCollection;

class TransactionInputCollectionTest extends AbstractTestCase
{
    /**
     * @expectedException \OutOfRangeException
     */
    public function testOutOfRange()
    {
        $collection = new TransactionInputCollection();
        $collection->getInput(10);
    }

    public function testSlice()
    {
        $in0 = new TransactionInput('4141414141414141414141414141414141414141414141414141414141414141', 0);
        $in1 = new TransactionInput('4141414141414141414141414141414141414141414141414141414141414141', 1);
        $arr = [$in0, $in1];
        $collection = new TransactionInputCollection($arr);
        $this->assertEquals(2, count($collection));
        $this->assertEquals(1, count($collection->slice(0, 1)));
        $this->assertSame($in0, $collection->getInput(0));
        $this->assertEquals($arr, $collection->getInputs());

    }
}
