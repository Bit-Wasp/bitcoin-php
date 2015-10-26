<?php

namespace BitWasp\Bitcoin\Tests\Collection\Transaction;

use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Collection\Transaction\TransactionCollection;
use BitWasp\Bitcoin\Transaction\TransactionInput;

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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidValue()
    {
        new TransactionCollection([new Transaction(), new TransactionInput('a', 1)]);
    }

    public function testClonesAreDistinguisable()
    {
        $t = new Transaction();
        $c = new TransactionCollection([$t]);

        $n = clone $c;
        $this->assertNotSame($c->get(0), $n->get(0));
    }
}
