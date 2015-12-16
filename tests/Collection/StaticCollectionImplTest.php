<?php

namespace BitWasp\Bitcoin\Tests\Collection;

use BitWasp\Bitcoin\Collection\Generic\Set;
use BitWasp\Bitcoin\Collection\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Buffertools\Buffer;

class StaticCollectionImplTest extends AbstractTestCase
{
    public function getInputCollection()
    {
        return new TransactionInputCollection([
            new TransactionInput(new OutPoint(Buffer::hex('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'), 5), new Script()),
            new TransactionInput(new OutPoint(Buffer::hex('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'), 10), new Script()),
        ]);
    }

    public function testArrayAccessRead()
    {
        $vout1 = 5;
        $vout2 = 10;
        $collection = $this->getInputCollection();
        $this->assertEquals($vout1, $collection[0]->getOutPoint()->getVout());
        $this->assertEquals($vout2, $collection[1]->getOutPoint()->getVout());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testArrayAccessOffsetReplace()
    {
        $collection = $this->getInputCollection();
        $collection[0] = new TransactionInput(new OutPoint(Buffer::hex('daaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'), 5), new Script());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testArrayAccessOffsetSet()
    {
        $collection = $this->getInputCollection();
        $collection[] = new TransactionInput(new OutPoint(Buffer::hex('caaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'), 5), new Script());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testArrayAccessOffsetUnset()
    {
        $collection = $this->getInputCollection();
        unset($collection[0]);
    }

    public function testArrayAccessOffsetExists()
    {
        $collection = $this->getInputCollection();
        $this->assertTrue(isset($collection[1]));
    }

    public function testArrayAccessOffsetGet()
    {
        $collection = new Set(['1']);
        $this->assertEquals('1', $collection[0]);
        $this->assertEquals('1', $collection->offsetGet(0));
    }

    /**
     * @expectedException \OutOfRangeException
     */
    public function testArrayAccessOffsetGetFailure()
    {
        $collection = new Set(['1']);
        $collection[20];
    }

    public function testIterates()
    {
        $iterator = $this->getInputCollection();
        $vout = 5;
        $key = 0;
        for ($iterator->rewind(); $iterator->valid(); $iterator->next()) {
            $value = $iterator->current();
            $this->assertEquals($vout, $value->getOutPoint()->getVout());
            $this->assertEquals($key, $iterator->key());
            $key++;
            $vout += 5;
        }

        $iterator->rewind();
        $this->assertEquals(0, $iterator->key());
    }

    public function testIteratorCurrent()
    {
        $collection = $this->getInputCollection();
        $c = 0;
        foreach ($collection as $idx => $value) {
            $this->assertEquals($value, $collection->current());
            $this->assertEquals($idx, $collection->key());
            $this->assertEquals($c, $idx);
            $c++;
        }
    }

    public function testIteratorSetCurrent()
    {
        $set = new Set(['1','2']);
        $this->assertEquals('1', $set->current());
    }

    public function testCountable()
    {
        $set = new Set(['1','2']);
        $this->assertEquals(2, count($set));
    }

    public function testAll()
    {
        $all = ['1','2'];
        $set = new Set($all);
        $this->assertEquals($all, $set->all());
    }

    public function testGet()
    {
        $all = ['1','2'];
        $set = new Set($all);
        $this->assertEquals('1', $set[0]);
    }

    /**
     * @expectedException \OutOfRangeException
     */
    public function testGetInvalid()
    {
        $set = new Set([]);
        $set[0];
    }
}
