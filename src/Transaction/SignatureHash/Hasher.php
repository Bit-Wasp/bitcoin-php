<?php

namespace BitWasp\Bitcoin\Transaction\SignatureHash;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Transaction\Mutator\TxMutator;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;

class Hasher implements SigHashInterface
{
    /**
     * @var TransactionInterface
     */
    private $transaction;

    /**
     * @param TransactionInterface $transaction
     */
    public function __construct(TransactionInterface $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Calculate the hash of the current transaction, when you are looking to
     * spend $txOut, and are signing $inputToSign. The SigHashType defaults to
     * SIGHASH_ALL, though SIGHASH_SINGLE, SIGHASH_NONE, SIGHASH_ANYONECANPAY
     * can be used.
     *
     * @param ScriptInterface $txOutScript
     * @param int $inputToSign
     * @param int $sighashType
     * @return BufferInterface
     * @throws \Exception
     */
    public function calculate(ScriptInterface $txOutScript, $inputToSign, $sighashType = SigHashInterface::ALL)
    {
        $math = Bitcoin::getMath();
        $tx = new TxMutator($this->transaction);
        $inputs = $tx->inputsMutator();
        $outputs = $tx->outputsMutator();

        // Default SIGHASH_ALL procedure: null all input scripts
        foreach ($inputs as $input) {
            $input->script(new Script);
        }

        $inputs[$inputToSign]->script($txOutScript);

        if ($math->cmp($math->bitwiseAnd($sighashType, 31), SigHashInterface::NONE) === 0) {
            // Set outputs to empty vector, and set sequence number of inputs to 0.
            $outputs->null();

            // Let the others update at will. Set sequence of inputs we're not signing to 0.
            foreach ($inputs as $i => $input) {
                if ($i !== $inputToSign) {
                    $input->sequence(0);
                }
            }
        } elseif ($math->cmp($math->bitwiseAnd($sighashType, 31), SigHashInterface::SINGLE) === 0) {
            // Resize output array to $inputToSign + 1, set remaining scripts to null,
            // and set sequence's to zero.
            $nOutput = $inputToSign;
            if ($nOutput >= count($this->transaction->getOutputs())) {
                return Buffer::hex('0100000000000000000000000000000000000000000000000000000000000000', 32, $math);
            }

            // Resize, set to null
            $outputs->slice(0, $nOutput + 1);
            for ($i = 0; $i < $nOutput; $i++) {
                $outputs[$i]->null();
            }

            // Let the others update at will. Set sequence of inputs we're not signing to 0
            foreach ($inputs as $i => $input) {
                if ($i !== $inputToSign) {
                    $input->sequence(0);
                }
            }
        }

        // This can happen regardless of whether it's ALL, NONE, or SINGLE
        if ($math->cmp($math->bitwiseAnd($sighashType, SigHashInterface::ANYONECANPAY), 0) > 0) {
            $input = $inputs[$inputToSign]->done();
            $inputs->null()->add($input);
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
