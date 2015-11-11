<?php

namespace BitWasp\Bitcoin\Tests\Collection\Transaction;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Collection\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Transaction\TransactionOutput;

class TransactionInputCollectionTest extends AbstractTestCase
{
    /**
     * @expectedException \OutOfRangeException
     */
    public function testOutOfRange()
    {
        $collection = new TransactionInputCollection();
        $collection[10];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRequiresInputInterface()
    {
        new TransactionInputCollection([
            new TransactionOutput(50, new Script())
        ]);
    }
}
