<?php

namespace BitWasp\Bitcoin\Script\Consensus;

use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class BitcoinConsensus implements ConsensusInterface
{
    /**
     * @param TransactionInterface $tx
     * @param ScriptInterface $scriptPubKey
     * @param int $nInputToSign
     * @param int $flags
     * @param int $amount
     * @return bool
     */
    public function verify(TransactionInterface $tx, ScriptInterface $scriptPubKey, $flags, $nInputToSign, $amount)
    {
        $error = 0;
        if ($flags & InterpreterInterface::VERIFY_WITNESS) {
            $verify = (bool) bitcoinconsensus_verify_script_with_amount($scriptPubKey->getBinary(), $amount, $tx->getWitnessBuffer()->getBinary(), $nInputToSign, $flags, $error);
        } else {
            $verify = (bool) bitcoinconsensus_verify_script($scriptPubKey->getBinary(), $tx->getBinary(), $nInputToSign, $flags, $error);
        }

        return $verify;
    }
}
