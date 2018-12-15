<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\PSBT;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;
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

    public function addNonWitnessTx(TransactionInterface $tx)
    {
        $outPoint = $this->psbt->getTransaction()->getInputs()[$this->nIn]->getOutPoint();
        if (!$outPoint->getTxId()->equals($tx->getTxId())) {
            throw new \RuntimeException("Non-witness txid differs from unsigned tx input {$this->nIn}");
        }
        if ($outPoint->getVout() > count($tx->getOutputs()) - 1) {
            throw new \RuntimeException("unsigned tx outpoint does not exist in this transaction");
        }
        $this->input = $this->input->withNonWitnessTx($tx);
    }

    public function addWitnessTxOut(TransactionOutputInterface $txOut)
    {
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
