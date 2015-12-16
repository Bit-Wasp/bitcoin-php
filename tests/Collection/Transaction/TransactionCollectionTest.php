<?php

namespace BitWasp\Bitcoin\Tests\Collection\Transaction;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Collection\Transaction\TransactionCollection;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Buffertools\Buffer;

class TransactionCollectionTest extends AbstractTestCase
{
    /**
     * @expectedException \OutOfRangeException
     */
    public function testOutOfRange()
    {
        $collection = new TransactionCollection();
        $collection[10];
    }

    public function testGetTransaction()
    {
        $t = new Transaction();
        $collection = new TransactionCollection([$t]);
        $this->assertSame($t, $collection[0]);

        $this->assertEquals([$t], $collection->all());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidValue()
    {
        new TransactionCollection([new Transaction(), new TransactionInput(new OutPoint(Buffer::hex('aa', 32), 1), new Script())]);
    }

    public function testClonesAreDistinguisable()
    {
        $t = new Transaction();
        $c = new TransactionCollection([$t]);

        $n = clone $c;
        $this->assertNotSame($c[0], $n[0]);
    }
}
