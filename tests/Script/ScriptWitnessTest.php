<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class ScriptWitnessTest extends AbstractTestCase
{
    public function testArrayAccessOffStaticBufferCollectionGet()
    {
        $collection = new ScriptWitness(new Buffer("\x01"));
        $this->assertEquals(new Buffer("\x01"), $collection[0]);
        $this->assertEquals(new Buffer("\x01"), $collection->offsetGet(0));
    }

    /**
     * @expectedException \OutOfRangeException
     */
    public function testArrayAccessOffStaticBufferCollectionGetFailure()
    {
        $collection = new ScriptWitness(new Buffer("\x01"));
        $collection[20];
    }

    public function testIteratorStaticBufferCollectionCurrent()
    {
        $StaticBufferCollection = new ScriptWitness(new Buffer("\x01"), new Buffer("\x02"));
        $this->assertEquals(new Buffer("\x01"), $StaticBufferCollection->current());
    }

    public function testCountable()
    {
        $StaticBufferCollection = new ScriptWitness(new Buffer("\x01"), new Buffer("\x02"));
        $this->assertEquals(2, count($StaticBufferCollection));
    }

    public function testAll()
    {
        $all = [new Buffer("\x01"),new Buffer("\x02")];
        $StaticBufferCollection = new ScriptWitness(new Buffer("\x01"), new Buffer("\x02"));
        $this->assertEquals($all, $StaticBufferCollection->all());
    }

    public function testGet()
    {
        $StaticBufferCollection = new ScriptWitness(new Buffer("\x01"), new Buffer("\x02"));
        $this->assertEquals(new Buffer("\x01"), $StaticBufferCollection[0]);
    }

    /**
     * @expectedException \OutOfRangeException
     */
    public function testGetInvalid()
    {
        $StaticBufferCollection = new ScriptWitness();
        $StaticBufferCollection[0];
    }
}