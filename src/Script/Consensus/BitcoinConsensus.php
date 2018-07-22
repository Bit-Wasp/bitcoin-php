<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script\Consensus;

use BitWasp\Bitcoin\Script\Consensus\Exception\BitcoinConsensusException;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class BitcoinConsensus implements ConsensusInterface
{
    /**
     * @param TransactionInterface $tx
     * @param ScriptInterface $scriptPubKey
     * @param int $flags
     * @param int $nInputToSign
     * @param int $amount
     * @return bool
     * @throws BitcoinConsensusException
     */
    public function verify(TransactionInterface $tx, ScriptInterface $scriptPubKey, int $flags, int $nInputToSign, int $amount): bool
    {
        if ($flags !== ($flags & BITCOINCONSENSUS_SCRIPT_FLAGS_VERIFY_ALL)) {
            throw new BitcoinConsensusException("Invalid flags for bitcoinconsensus");
        }

        $error = 0;
        if ($flags & InterpreterInterface::VERIFY_WITNESS) {
            $verify = (bool) bitcoinconsensus_verify_script_with_amount($scriptPubKey->getBinary(), $amount, $tx->getBinary(), $nInputToSign, $flags, $error);
        } else {
            $verify = (bool) bitcoinconsensus_verify_script($scriptPubKey->getBinary(), $tx->getBaseSerialization()->getBinary(), $nInputToSign, $flags, $error);
        }

        return $verify;
    }
}
