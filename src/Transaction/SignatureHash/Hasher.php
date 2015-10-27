<?php

namespace BitWasp\Bitcoin\Transaction\SignatureHash;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Transaction\Mutator\TxMutator;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;

class Hasher implements SignatureHashInterface
{
    /**
     * @var TransactionInterface
     */
    private $transaction;

    /**
     * @var int
     */
    private $nInputs;

    /**
     * @var int
     */
    private $nOutputs;

    /**
     * @param TransactionInterface $transaction
     */
    public function __construct(TransactionInterface $transaction)
    {
        $this->transaction = $transaction;
        $this->nInputs = count($this->transaction->getInputs());
        $this->nOutputs = count($this->transaction->getOutputs());
    }

    /**
     * Calculate the hash of the current transaction, when you are looking to
     * spend $txOut, and are signing $inputToSign. The SigHashType defaults to
     * SIGHASH_ALL, though SIGHASH_SINGLE, SIGHASH_NONE, SIGHASH_ANYONECANPAY
     * can be used.
     *
     * @param ScriptInterface $txOutScript
     * @param $inputToSign
     * @param int $sighashType
     * @return Buffer
     * @throws \Exception
     */
    public function calculate(ScriptInterface $txOutScript, $inputToSign, $sighashType = SignatureHashInterface::SIGHASH_ALL)
    {
        $math = Bitcoin::getMath();
        $tx = new TxMutator($this->transaction);
        $vin = $tx->inputsMutator();
        $vout = $tx->outputsMutator();

        // Default SIGHASH_ALL procedure: null all input scripts
        foreach ($vin as $input) {
            $input->script(new Script);
        }

        $vin->offsetGet($inputToSign)
            ->script($txOutScript);

        if ($math->bitwiseAnd($sighashType, 31) == SignatureHashInterface::SIGHASH_NONE) {
            // Set outputs to empty vector, and set sequence number of inputs to 0.
            $vout->null();

            // Let the others update at will. Set sequence of inputs we're not signing to 0.
            foreach ($vin as $i => $input) {
                if ($i !== $inputToSign) {
                    $input->sequence(0);
                }
            }

        } elseif ($math->bitwiseAnd($sighashType, 31) == SignatureHashInterface::SIGHASH_SINGLE) {
            // Resize output array to $inputToSign + 1, set remaining scripts to null,
            // and set sequence's to zero.
            $nOutput = $inputToSign;
            if ($nOutput >= $this->nOutputs) {
                return Buffer::hex('0100000000000000000000000000000000000000000000000000000000000000', 32, $math);
            }

            // Resize, set to null
            $vout->slice(0, $nOutput + 1);
            for ($i = 0; $i < $nOutput; $i++) {
                $vout[$i]->null();
            }

            // Let the others update at will. Set sequence of inputs we're not signing to 0
            foreach ($vin as $i => $input) {
                if ($i != $inputToSign) {
                    $input->sequence(0);
                }
            }
        }

        // This can happen regardless of whether it's ALL, NONE, or SINGLE
        if ($math->bitwiseAnd($sighashType, SignatureHashInterface::SIGHASH_ANYONECANPAY)) {
            $input = $vin->offsetGet($inputToSign)->done();
            $vin->null()->add($input);
        }

        return Hash::sha256d(
            Buffertools::concat(
                $tx
                    ->done()
                    ->getBuffer(),
                Buffertools::flipBytes(Buffer::int($sighashType, 4, $math))
            )
        );
    }
}
