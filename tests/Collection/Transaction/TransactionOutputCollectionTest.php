<?php

namespace BitWasp\Bitcoin\Tests\Collection\Transaction;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Collection\Transaction\TransactionOutputCollection;
use BitWasp\Buffertools\Buffer;

class TransactionOutputCollectionTest extends AbstractTestCase
{
    /**
     * @expectedException \OutOfRangeException
     */
    public function testOutOfRange()
    {
        $collection = new TransactionOutputCollection();
        $collection[10];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRequiresOutputInterface()
    {
        new TransactionOutputCollection([
            new TransactionInput(new OutPoint(Buffer::hex('aa', 32), 50), new Script())
        ]);
    }
}
