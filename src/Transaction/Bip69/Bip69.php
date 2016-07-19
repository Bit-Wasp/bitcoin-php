<?php

namespace BitWasp\Bitcoin\Transaction\Bip69;

use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Transaction\Mutator\TxMutator;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class Bip69
{
    /**
     * @param TransactionInputInterface[] $vTxin
     * @return TransactionInputInterface[]
     */
    public function sortInputs(array $vTxin)
    {
        usort($vTxin, [$this, 'compareInputs']);
        return $vTxin;
    }

    /**
     * @param TransactionInputInterface $vin1
     * @param TransactionInputInterface $vin2
     * @return bool
     */
    public function compareInputs(TransactionInputInterface $vin1, TransactionInputInterface $vin2)
    {
        $outpoint1 = $vin1->getOutPoint();
        $outpoint2 = $vin2->getOutPoint();

        $cmpTxId = strcmp($outpoint1->getTxId()->getBinary(), $outpoint2->getTxId()->getBinary());

        return ($cmpTxId !== 0) ? $cmpTxId : $outpoint1->getVout() - $outpoint2->getVout();
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
     * @param TransactionOutputInterface $vout1
     * @param TransactionOutputInterface $vout2
     * @return bool
     */
    public function compareOutputs(TransactionOutputInterface $vout1, TransactionOutputInterface $vout2)
    {
        $value = $vout1->getValue() - $vout2->getValue();

        return ($value !== 0) ? $value : strcmp($vout1->getScript()->getBinary(), $vout2->getScript()->getBinary());
    }

    /**
     * @param TransactionInterface $tx
     * @return bool
     */
    public function check(TransactionInterface $tx)
    {
        $inputs = $tx->getInputs();
        $outputs = $tx->getOutputs();

        return $this->sortInputs($inputs) === $inputs && $this->sortOutputs($outputs) === $outputs;
    }

    /**
     * @param TransactionInputInterface[] $inputs
     * @param ScriptWitnessInterface[] $witnesses
     * @return array
     * @throws \Exception
     */
    public function sortInputsAndWitness(array $inputs, array $witnesses)
    {
        if (count($inputs) !== count($witnesses)) {
            throw new \Exception('Number of inputs must match witnesses');
        }

        uasort($inputs, [$this, 'compareInputs']);

        $vWitness = [];
        foreach ($inputs as $key => $input) {
            $vWitness[] = $witnesses[$key];
        }

        return [$inputs, $vWitness];
    }

    /**
     * @param TransactionInterface $tx
     * @return TransactionInterface
     */
    public function mutate(TransactionInterface $tx)
    {
        if (count($tx->getWitnesses()) > 0) {
            list ($vTxin, $vWit) = $this->sortInputsAndWitness($tx->getInputs(), $tx->getWitnesses());
        } else {
            $vTxin = $this->sortInputs($tx->getInputs());
            $vWit = [];
        }

        return (new TxMutator($tx))
            ->inputs($vTxin)
            ->outputs($this->sortOutputs($tx->getOutputs()))
            ->witness($vWit)
            ->done();
    }
}
