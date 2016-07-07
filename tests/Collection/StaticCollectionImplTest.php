<?php

namespace BitWasp\Bitcoin\Tests\Collection;

use BitWasp\Bitcoin\Collection\Generic\Set;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class StaticCollectionImplTest extends AbstractTestCase
{
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
