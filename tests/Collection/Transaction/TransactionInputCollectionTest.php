<?php

namespace BitWasp\Bitcoin\Tests\Collection\Transaction;

use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Collection\Transaction\TransactionInputCollection;

class TransactionInputCollectionTest extends AbstractTestCase
{
    /**
     * @expectedException \OutOfRangeException
     */
    public function testOutOfRange()
    {
        $collection = new TransactionInputCollection();
        $collection->get(10);
    }
}
