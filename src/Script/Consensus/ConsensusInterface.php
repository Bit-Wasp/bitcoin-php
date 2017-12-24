<?php

namespace BitWasp\Bitcoin\Script\Consensus;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

interface ConsensusInterface
{
    /**
     * @param TransactionInterface $tx
     * @param ScriptInterface $scriptPubKey
     * @param integer $nInputToSign
     * @param int $flags
     * @param integer $amount
     * @return bool
     */
    public function verify(TransactionInterface $tx, ScriptInterface $scriptPubKey, int $flags, int $nInputToSign, int $amount): bool;
}
