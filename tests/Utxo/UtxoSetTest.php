<?php

namespace BitWasp\Bitcoin\Tests\Utxo;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputCollection;
use BitWasp\Bitcoin\Utxo\UtxoSet;
use Doctrine\Common\Cache\ArrayCache;

class UtxoSetTest extends AbstractTestCase
{
    public function testUtxoSet()
    {
        /// test coinbase
        $tx = new Transaction(
            Transaction::DEFAULT_VERSION,
            new TransactionInputCollection([
                new TransactionInput(
                    '0000000000000000000000000000000000000000000000000000000000000000',
                    TransactionInterface::MAX_LOCKTIME,
                    new Script()
                )
            ]),
            new TransactionOutputCollection([
                new TransactionOutput(
                    100000000,
                    new Script()
                )
            ])
        );
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
