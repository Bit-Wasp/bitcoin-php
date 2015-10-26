<?php


namespace BitWasp\Bitcoin\Tests\Transaction\Mutator;

use BitWasp\Bitcoin\Collection\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Mutator\InputCollectionMutator;
use BitWasp\Bitcoin\Transaction\Mutator\InputMutator;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Buffertools\Buffer;

class InputCollectionMutatorTest extends AbstractTestCase
{

    public function testMutatesOutputCollection()
    {
        $vout1 = -1;
        $script1 = new Script(new Buffer('0'));
        $vout2 = -2;
        $script2 = new Script(new Buffer('1'));
        $collection = new TransactionInputCollection([
            new TransactionInput('a', 0, new Script()),
            new TransactionInput('b', 0, new Script()),
        ]);

        $mutator = new InputCollectionMutator($collection);
        $mutator->applyTo(0, function (InputMutator $i) use ($vout1, $script1) {
            $i  ->script($script1)
                ->vout($vout1);
        });

        $mutator->applyTo(1, function (InputMutator $i) use ($vout2, $script2) {
            $i  ->script($script2)
                ->vout($vout2);
        });

        $new = $mutator->get();
        $this->assertEquals('a', $new->get(0)->getTransactionId());
        $this->assertEquals($vout1, $new->get(0)->getVout());
        $this->assertEquals($script1, $new->get(0)->getScript());
        $this->assertEquals('b', $new->get(1)->getTransactionId());
        $this->assertEquals($vout2, $new->get(1)->getVout());
        $this->assertEquals($script2, $new->get(1)->getScript());
    }


    /**
     * @expectedException \RuntimeException
     */
    public function testInvalidSlice()
    {
        $collection = new TransactionInputCollection([
        ]);

        $mutator = new InputCollectionMutator($collection);
        $mutator->slice(0, 1);
    }

    public function testNull()
    {
        $collection = new TransactionInputCollection([
            new TransactionInput('a', 5, new Script()),
            new TransactionInput('b', 10, new Script()),
        ]);

        $mutator = new InputCollectionMutator($collection);
        $mutator->null();
        $outputs = $mutator->get();

        $this->assertEquals(0, count($outputs));
    }

    /**
     * @expectedException \OutOfRangeException
     */
    public function testRejectsInvalidIndex()
    {
        $collection = new TransactionInputCollection([
        ]);

        $mutator = new InputCollectionMutator($collection);
        $mutator->update(1, new TransactionInput('a', 1, new Script()));
    }
}
