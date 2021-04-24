<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction\Mutator;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Mutator\InputCollectionMutator;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Buffertools\Buffer;

class InputCollectionMutatorTest extends AbstractTestCase
{

    public function testMutatesInputCollection()
    {
        $txid1 = Buffer::hex('ab', 32);
        $txid2 = Buffer::hex('aa', 32);

        $script1 = new Script(new Buffer('0'));
        $script2 = new Script(new Buffer('1'));

        $collection = [
            new TransactionInput(new OutPoint($txid1, 0), new Script()),
            new TransactionInput(new OutPoint($txid2, 0), new Script()),
        ];

        $mutator = new InputCollectionMutator($collection);
        $mutator[0]->script($script1);
        $mutator[1]->script($script2);

        $new = $mutator->done();
        $this->assertEquals($script1, $new[0]->getScript());
        $this->assertEquals($script2, $new[1]->getScript());
    }


    /**
     * @expectedException \RuntimeException
     */
    public function testInvalidSlice()
    {
        $collection = [
        ];
        
        $mutator = new InputCollectionMutator($collection);
        $this->expectException(\RuntimeException::class);
        $mutator->slice(0, 1);
    }

    public function testNull()
    {
        $collection = [
            new TransactionInput(new OutPoint(Buffer::hex('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'), 5), new Script()),
            new TransactionInput(new OutPoint(Buffer::hex('baaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'), 10), new Script()),
        ];

        $mutator = new InputCollectionMutator($collection);
        $mutator->null();
        $outputs = $mutator->done();

        $this->assertEquals(0, count($outputs));
    }

    public function testSet()
    {
        $collection = [
            new TransactionInput(new OutPoint(Buffer::hex('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'), 5), new Script()),
            new TransactionInput(new OutPoint(Buffer::hex('baaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'), 10), new Script()),
        ];

        $mutator = new InputCollectionMutator($collection);
        $new = new TransactionInput(new OutPoint(Buffer::hex('baaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'), 11), new Script());
        $mutator->set(1, $new);
        $newCollection = $mutator->done();
        $this->assertTrue($newCollection[1]->equals($new));
    }

    public function testInvalidIndex()
    {
        $mutator = new InputCollectionMutator([]);
        $this->expectException(\OutOfRangeException::class);
        $mutator->offsetGet(10);
    }
}
