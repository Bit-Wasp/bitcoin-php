<?php

namespace BitWasp\Bitcoin\Tests\Transaction;


use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionCollection;

class TransactionCollectionTest extends AbstractTestCase
{
    /**
     * @expectedException \OutOfRangeException
     */
    public function testOutOfRange()
    {
        $collection = new TransactionCollection();
        $collection->getTransaction(10);
    }

    public function testSlice()
    {
        $collection = new TransactionCollection([new Transaction(), new Transaction()]);
        $this->assertEquals(2, count($collection));
        $this->assertEquals(2, count($collection->slice(0, 1)));
    }
}