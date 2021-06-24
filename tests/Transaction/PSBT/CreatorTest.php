<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction\PSBT;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\PSBT\Creator;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

class CreatorTest extends AbstractTestCase
{
    public function testCreatesPsbt()
    {
        $version = 2;
        $locktime = 0;
        $txin1 = new TransactionInput(new OutPoint(Buffer::hex("75ddabb27b8845f5247975c8a5ba7c6f336c4570708ebe230caf6db5217ae858"), 0), new Script());
        $txin2 = new TransactionInput(new OutPoint(Buffer::hex("1dea7cd05979072a3578cab271c02244ea8a090bbb46aa680a65ecd027048d83"), 1), new Script());

        $txOut1 = new TransactionOutput(149990000, ScriptFactory::fromHex("0014d85c2b71d0060b09c9886aeb815e50991dda124d"));
        $txOut2 = new TransactionOutput(100000000, ScriptFactory::fromHex("001400aea9a2e5f0f876a588df5546e8742d1d87008f"));

        $tx = new Transaction($version, [$txin1, $txin2,], [$txOut1, $txOut2], [], $locktime);

        $creator = new Creator();
        $psbt = $creator->createPsbt($tx);
        $this->assertEquals(
            "70736274ff01009a020000000258e87a21b56daf0c23be8e7070456c336f7cbaa5c8757924f545887bb2abdd750000000000ffffffff838d0427d0ec650a68aa46bb0b098aea4422c071b2ca78352a077959d07cea1d0100000000ffffffff0270aaf00800000000160014d85c2b71d0060b09c9886aeb815e50991dda124d00e1f5050000000016001400aea9a2e5f0f876a588df5546e8742d1d87008f000000000000000000",
            $psbt->getBuffer()->getHex()
        );
    }
}
