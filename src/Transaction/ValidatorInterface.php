<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\Consensus\ConsensusInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;

interface ValidatorInterface
{
    /**
     * @param ConsensusInterface $consensus
     * @param int $nInput
     * @param ScriptInterface $scriptPubKey
     * @return bool
     */
    public function checkSignature(ConsensusInterface $consensus, $nInput, $amount, ScriptInterface $scriptPubKey);

    /**
     * @param ConsensusInterface $consensus
     * @param TransactionOutputInterface[] $outputs
     * @return bool
     */
    public function checkSignatures(ConsensusInterface $consensus, array $outputs);
}
