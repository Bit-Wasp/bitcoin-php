<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction\Mutator;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Mutator\InputMutator;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Buffertools\Buffer;

class InputMutatorTest extends AbstractTestCase
{
    public function testMutatesInputs()
    {
        $input = new TransactionInput(
            new OutPoint(Buffer::hex('0000000000000000000000000000000000000000000000000000000000000000'), 0),
            new Script(),
            0
        );

        $newTxid = Buffer::hex('0123456701234567012345670123456701234567012345670123456701234567', 32);
        $newVout = 10;
        $newOutPoint = new OutPoint($newTxid, $newVout);
        $newScript = new Script(new Buffer('00'));
        $newSequence = 99;

        $mutator = new InputMutator($input);
        $new = $mutator
            ->outpoint($newOutPoint)
            ->script($newScript)
            ->sequence($newSequence)
            ->done();

        $this->assertEquals($newTxid, $new->getOutPoint()->getTxId());
        $this->assertEquals($newVout, $new->getOutPoint()->getVout());
        $this->assertEquals($newScript, $new->getScript());
        $this->assertEquals($newSequence, $new->getSequence());
    }

    public function testNull()
    {
        $input = new TransactionInput(
            new OutPoint(Buffer::hex('0203000000000000000000000000000000000000000000000000000000000000'), 0),
            new Script(),
            0
        );

        $mutator = new InputMutator($input);
        $new = $mutator
            ->null()
            ->done();

        $this->assertEquals(Buffer::hex('0000000000000000000000000000000000000000000000000000000000000000', 32), $new->getOutPoint()->getTxId());
        $this->assertEquals(0xffffffff, $new->getOutPoint()->getVout());
    }
}
