<?php

namespace BitWasp\Bitcoin\Tests\Collection\Transaction;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionInput;
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRequiresOutputInterface()
    {
        new TransactionOutputCollection([
            new TransactionInput('a', 50, new Script())
        ]);
    }
}
