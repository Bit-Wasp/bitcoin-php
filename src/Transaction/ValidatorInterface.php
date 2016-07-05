<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\Consensus\ConsensusInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;

interface ValidatorInterface
{
    /**
     * @param ConsensusInterface $consensus
     * @param int $nInput
     * @param int $flags
     * @param TransactionOutputInterface $txOut
     * @return bool
     */
    public function checkSignature(ConsensusInterface $consensus, $flags, $nInput, TransactionOutputInterface $txOut);

    /**
     * @param ConsensusInterface $consensus
     * @param int $flags
     * @param TransactionOutputInterface[] $outputs
     * @return bool
     */
    public function checkSignatures(ConsensusInterface $consensus, $flags, array $outputs);
}
