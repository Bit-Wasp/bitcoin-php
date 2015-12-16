<?php

namespace BitWasp\Bitcoin\Tests\Utxo;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;

class UtxoTest extends AbstractTestCase
{
    public function getOutput()
    {
        return new TransactionOutput(
            50,
            new Script()
        );
    }

    public function testUtxo()
    {
        $txid = Buffer::hex('3a182308716d461ad310f43a83d407f9e930e728f918fb5ed9f43679a8fdc1d8', 32);
        $vout = '0';
        $output = $this->getOutput();
        $outpoint = new OutPoint($txid, $vout);
        $utxo = new Utxo($outpoint, $output);
        $this->assertSame($outpoint, $utxo->getOutPoint());
        $this->assertSame($output, $utxo->getOutput());
    }
}
