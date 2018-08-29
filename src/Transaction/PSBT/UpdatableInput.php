<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\PSBT;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\OutPointInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class UpdatableInput
{
    private $psbt;
    private $nIn;
    private $input;

    public function __construct(
        PSBT $psbt,
        int $nIn,
        PSBTInput $input
    ) {
        $this->psbt = $psbt;
        $this->nIn = $nIn;
        $this->input = $input;
    }

    public function input(): PSBTInput
    {
        return $this->input;
    }

    private function findOurOutPoint(TransactionInterface $tx, OutPointInterface &$o = null)
    {
        $outPoint = $this->psbt->getTransaction()->getInputs()[$this->nIn]->getOutPoint();
        if (!$outPoint->getTxId()->equals($tx->getTxId())) {
            throw new \RuntimeException("Non-witness txid differs from unsigned tx input {$this->nIn}");
        }
        if ($outPoint->getVout() >= count($this->psbt->getTransaction()->getOutputs())) {
            throw new \RuntimeException("unsigned tx outpoint does not exist in this transaction");
        }
        $o = $outPoint;
    }

    public function addNonWitnessTx(TransactionInterface $tx)
    {
        if ($this->input->hasNonWitnessTx()) {
            return;
        }
        $this->findOurOutPoint($tx);
        $this->input = $this->input->withNonWitnessTx($tx);
    }

    public function addWitnessTx(TransactionInterface $tx)
    {
        if ($this->input->hasWitnessTxOut()) {
            return;
        }
        /** @var OutPointInterface $outPoint */
        $outPoint = null;
        $this->findOurOutPoint($tx, $outPoint);
        $this->input = $this->input->withWitnessTxOut($tx->getOutput($outPoint->getVout()));
    }

    public function addWitnessTxOut(TransactionOutputInterface $txOut)
    {
        if ($this->input->hasWitnessTxOut()) {
            return;
        }
        $this->input = $this->input->withWitnessTxOut($txOut);
    }

    public function addRedeemScript(ScriptInterface $script)
    {
        $this->input = $this->input->withRedeemScript($script);
    }

    public function addWitnessScript(ScriptInterface $script)
    {
        $this->input = $this->input->withWitnessScript($script);
    }

    public function addDerivation(PublicKeyInterface $key, PSBTBip32Derivation $derivation)
    {
        $this->input = $this->input->withDerivation($key, $derivation);
    }
}
