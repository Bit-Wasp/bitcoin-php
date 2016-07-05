<?php

namespace BitWasp\Bitcoin\Script\Consensus;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class BitcoinConsensus implements ConsensusInterface
{
    /**
     * @param TransactionInterface $tx
     * @param ScriptInterface $scriptPubKey
     * @param int $nInputToSign
     * @param int $flags
     * @param int $amount
     * @param ScriptWitnessInterface|null $witness
     * @return bool
     */
    public function verify(TransactionInterface $tx, ScriptInterface $scriptPubKey, $flags, $nInputToSign, $amount, ScriptWitnessInterface $witness = null)
    {
        $error = 0;
        return (bool) bitcoinconsensus_verify_script(
            $scriptPubKey->getBinary(),
            $tx->getBinary(),
            $nInputToSign,
            $flags,
            $error
        );
    }
}
