<?php

namespace BitWasp\Bitcoin\Tests\Collection\Transaction;

use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Collection\Transaction\TransactionCollection;

class TransactionCollectionTest extends AbstractTestCase
{
    /**
     * @expectedException \OutOfRangeException
     */
    public function testOutOfRange()
    {
        $collection = new TransactionCollection();
        $collection->get(10);
    }

    public function testGetTransaction()
    {
        $t = new Transaction();
        $collection = new TransactionCollection([$t]);
        $this->assertSame($t, $collection->get(0));

        $this->assertEquals([$t], $collection->all());
    }
}
