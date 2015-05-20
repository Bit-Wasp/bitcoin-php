<?php

namespace BitWasp\Bitcoin\Tests\Utxo;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Utxo\Utxo;

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
        $txid = '3a182308716d461ad310f43a83d407f9e930e728f918fb5ed9f43679a8fdc1d8';
        $vout = '0';
        $output = $this->getOutput();
        $utxo = new Utxo($txid, $vout, $output);
        $this->assertEquals($txid, $utxo->getTransactionId());
        $this->assertEquals($vout, $utxo->getVout());
        $this->assertEquals($output, $utxo->getOutput());
    }
}
