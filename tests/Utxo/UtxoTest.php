<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Utxo;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;

class UtxoTest extends AbstractTestCase
{
    public function testUtxo()
    {
        $output = new TransactionOutput(
            50,
            new Script()
        );

        $outpoint = new OutPoint(
            Buffer::hex('3a182308716d461ad310f43a83d407f9e930e728f918fb5ed9f43679a8fdc1d8', 32),
            0
        );

        $utxo = new Utxo($outpoint, $output);
        $this->assertSame($outpoint, $utxo->getOutPoint());
        $this->assertSame($output, $utxo->getOutput());
    }
}
