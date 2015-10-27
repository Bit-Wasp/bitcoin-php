<?php

namespace BitWasp\Bitcoin\Tests\Transaction\Mutator;

use BitWasp\Bitcoin\Collection\Transaction\TransactionOutputCollection;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Mutator\OutputCollectionMutator;
use BitWasp\Bitcoin\Transaction\Mutator\OutputMutator;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

class OutputCollectionMutatorTest extends AbstractTestCase
{
    public function testMutatesOutputCollection()
    {
        $value1 = -1;
        $script1 = new Script(new Buffer('0'));
        $value2 = -2;
        $script2 = new Script(new Buffer('1'));
        $collection = new TransactionOutputCollection([
            new TransactionOutput(5, new Script()),
            new TransactionOutput(10, new Script()),
        ]);

        $mutator = new OutputCollectionMutator($collection->all());
        $mutator->applyTo(0, function (OutputMutator $o) use ($value1, $script1) {
            $o  ->script($script1)
                ->value($value1);
        });

        $mutator->applyTo(1, function (OutputMutator $o) use ($value2, $script2) {
            $o  ->script($script2)
                ->value($value2);
        });

        $new = $mutator->done();
        $this->assertEquals($value1, $new->get(0)->getValue());
        $this->assertEquals($script1, $new->get(0)->getScript());
        $this->assertEquals($value2, $new->get(1)->getValue());
        $this->assertEquals($script2, $new->get(1)->getScript());
    }

    public function testAdds()
    {
        $collection = new TransactionOutputCollection([
            new TransactionOutput(5, new Script()),
            new TransactionOutput(10, new Script()),
        ]);

        $mutator = new OutputCollectionMutator($collection->all());
        $mutator->add(new TransactionOutput(15, new Script()));
        $outputs = $mutator->done();

        $this->assertEquals(3, count($outputs));
    }

    public function testSlice()
    {
        $collection = new TransactionOutputCollection([
            new TransactionOutput(5, new Script()),
            new TransactionOutput(10, new Script()),
        ]);

        $mutator = new OutputCollectionMutator($collection->all());
        $mutator->slice(0, 1);
        $outputs = $mutator->done();

        $this->assertEquals(1, count($outputs));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvalidSlice()
    {
        $collection = new TransactionOutputCollection([
        ]);

        $mutator = new OutputCollectionMutator($collection->all());
        $mutator->slice(0, 1);
    }

    public function testNull()
    {
        $collection = new TransactionOutputCollection([
            new TransactionOutput(5, new Script()),
            new TransactionOutput(10, new Script()),
        ]);

        $mutator = new OutputCollectionMutator($collection->all());
        $mutator->null();
        $outputs = $mutator->done();

        $this->assertEquals(0, count($outputs));
    }

    /**
     * @expectedException \OutOfRangeException
     */
    public function testRejectsInvalidIndex()
    {
        $collection = new TransactionOutputCollection([
        ]);

        $mutator = new OutputCollectionMutator($collection->all());
        $mutator->update(1, new TransactionOutput(1, new Script()));
    }
}
