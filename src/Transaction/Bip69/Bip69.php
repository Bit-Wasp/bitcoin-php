<?php

namespace BitWasp\Bitcoin\Transaction\Bip69;

use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class Bip69
{
    /**
     * @param array $vTxin
     * @return array
     */
    public function sortInputs(array $vTxin)
    {
        usort($vTxin, [$this, 'compareInputs']);
        return $vTxin;
    }

    /**
     * @param TransactionInputInterface $tx1
     * @param TransactionInputInterface $tx2
     * @return bool
     */
    public function compareInputs(TransactionInputInterface $tx1, TransactionInputInterface $tx2)
    {
        $outpoint1 = $tx1->getOutPoint();
        $outpoint2 = $tx2->getOutPoint();
        $txid1 = $outpoint1->getTxId();
        $txid2 = $outpoint2->getTxId();
        return strcmp($txid1->getBinary(), $txid2->getBinary()) || $outpoint1->getVout() - $outpoint2->getVout();
    }

    /**
     * @param TransactionOutputInterface[] $vTxout
     * @return TransactionOutputInterface[]
     */
    public function sortOutputs($vTxout)
    {
        usort($vTxout, [$this, 'compareOutputs']);
        return $vTxout;
    }

    /**
     * @param TransactionOutputInterface $tx1
     * @param TransactionOutputInterface $tx2
     * @return bool
     */
    public function compareOutputs(TransactionOutputInterface $tx1, TransactionOutputInterface $tx2)
    {
        return $tx1->getValue() - $tx2->getValue() || strcmp($tx1->getScript()->getBinary(), $tx2->getScript()->getBinary());
    }

    /**
     * @param TransactionInterface $tx
     * @return bool
     */
    public function check(TransactionInterface $tx)
    {
        $inputs = $tx->getInputs()->all();
        $sortedInputs = $this->sortInputs($inputs);

        $outputs = $tx->getOutputs()->all();
        $sortedOutputs = $this->sortOutputs($outputs);

        $valid = $sortedInputs === $inputs && $sortedOutputs === $sortedOutputs;

        return $valid;
    }
}
