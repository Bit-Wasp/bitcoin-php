<?php

namespace BitWasp\Bitcoin\Script\Consensus;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class BitcoinConsensus implements ConsensusInterface
{
    /**
     * @var int
     */
    private $flags;

    /**
     * @param int $flags
     */
    public function __construct($flags)
    {
        $this->flags = $flags;
    }

    /**
     * @param TransactionInterface $tx
     * @param ScriptInterface $scriptPubKey
     * @param int $nInputToSign
     * @param int $amount
     * @param ScriptWitnessInterface|null $witness
     * @return bool
     */
    public function verify(TransactionInterface $tx, ScriptInterface $scriptPubKey, $nInputToSign, $amount, ScriptWitnessInterface $witness = null)
    {
        $error = 0;
        return (bool) bitcoinconsensus_verify_script(
            $scriptPubKey->getBinary(),
            $tx->getBinary(),
            $nInputToSign,
            $this->flags,
            $error
        );
    }
}
