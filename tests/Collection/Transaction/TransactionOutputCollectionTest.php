<?php

namespace BitWasp\Bitcoin\Tests\Collection\Transaction;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Collection\Transaction\TransactionOutputCollection;

class TransactionOutputCollectionTest extends AbstractTestCase
{
    /**
     * @expectedException \OutOfRangeException
     */
    public function testOutOfRange()
    {
        $collection = new TransactionOutputCollection();
        $collection->get(10);
    }
}
