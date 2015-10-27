<?php

namespace BitWasp\Bitcoin\Tests\Transaction\Mutator;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Mutator\InputMutator;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Buffertools\Buffer;

class InputMutatorTest extends AbstractTestCase
{
    public function testMutatesInputs()
    {
        $input = new TransactionInput(
            '0000000000000000000000000000000000000000000000000000000000000000',
            0,
            new Script(),
            0
        );

        $newTxid = '0123456701234567012345670123456701234567012345670123456701234567';
        $newVout = 10;
        $newScript = new Script(new Buffer('00'));
        $newSequence = 99;

        $mutator = new InputMutator($input);
        $new = $mutator
            ->txid($newTxid)
            ->vout($newVout)
            ->script($newScript)
            ->sequence($newSequence)
            ->done();

        $this->assertEquals($newTxid, $new->getTransactionId());
        $this->assertEquals($newVout, $new->getVout());
        $this->assertEquals($newScript, $new->getScript());
        $this->assertEquals($newSequence, $new->getSequence());
    }

    public function testNull()
    {
        $input = new TransactionInput(
            '0203000000000000000000000000000000000000000000000000000000000000',
            0,
            new Script(),
            0
        );

        $mutator = new InputMutator($input);
        $new = $mutator
            ->null()
            ->done();

        $this->assertEquals('0000000000000000000000000000000000000000000000000000000000000000', $new->getTransactionId());
        $this->assertEquals(0xffffffff, $new->getVout());
    }
}
