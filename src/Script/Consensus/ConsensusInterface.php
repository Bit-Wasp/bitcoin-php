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
     */
    public function verify(TransactionInterface $tx, ScriptInterface $scriptPubKey, $nInputToSign);
}