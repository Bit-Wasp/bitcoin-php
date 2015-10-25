<?php

namespace BitWasp\Bitcoin\Tests\Utxo;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Utxo\UtxoSet;
use Doctrine\Common\Cache\ArrayCache;

class UtxoSetTest extends AbstractTestCase
{
    public function testUtxoSet()
    {
        /// test coinbase
        $builder = new TxBuilder();
        $builder
            ->input('0000000000000000000000000000000000000000000000000000000000000000', TransactionInterface::MAX_LOCKTIME)
            ->output(100000000, new Script());
        $tx = $builder->get();
        $txid = $tx->getTxId()->getHex();

        $utxoSet = new UtxoSet(new ArrayCache());
        $this->assertFalse($utxoSet->contains($txid, 0));

        $utxoSet->save($tx);
        $this->assertEquals(1, $utxoSet->size());
        $this->assertTrue($utxoSet->contains($txid, 0));

        $utxo = $utxoSet->fetch($txid, 0);
        $this->assertEquals($txid, $utxo->getTransactionId());
        $this->assertEquals(0, $utxo->getVout());
        $this->assertEquals($tx->getOutputs()->get(0), $utxo->getOutput());
    }
}
