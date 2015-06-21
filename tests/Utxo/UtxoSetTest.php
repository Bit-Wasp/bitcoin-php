<?php

namespace BitWasp\Bitcoin\Tests\Utxo;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Utxo\UtxoSet;
use Doctrine\Common\Cache\ArrayCache;

class UtxoSetTest extends AbstractTestCase
{
    public function testUtxoSet()
    {
        /// test coinbase
        $tx = new Transaction();
        $tx->getInputs()->addInput(new TransactionInput(
            '0000000000000000000000000000000000000000000000000000000000000000',
            TransactionInterface::MAX_LOCKTIME,
            new Script()
        ));
        $tx->getOutputs()->addOutput(new TransactionOutput(
            100000000,
            new Script()
        ));
        $txid = $tx->getTransactionId();

        $utxoSet = new UtxoSet(new ArrayCache());
        $this->assertFalse($utxoSet->contains($txid, 0));

        $utxoSet->save($tx);
        $this->assertEquals(1, $utxoSet->size());
        $this->assertTrue($utxoSet->contains($txid, 0));

        $utxo = $utxoSet->fetch($txid, 0);
        $this->assertEquals($txid, $utxo->getTransactionId());
        $this->assertEquals(0, $utxo->getVout());
        $this->assertEquals($tx->getOutputs()->getOutput(0), $utxo->getOutput());
    }
}
