<?php

namespace BitWasp\Bitcoin\Script\Consensus;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class BitcoinConsensus
{
    /**
     * @var Flags
     */
    private $flags;

    /**
     * @param Flags $flags
     */
    public function __construct(Flags $flags)
    {
        $this->flags = $flags;
    }

    /**
     * @param TransactionInterface $tx
     * @param ScriptInterface $scriptPubKey
     * @param int $nInputToSign
     * @return bool
     */
    public function verify(TransactionInterface $tx, ScriptInterface $scriptPubKey, $nInputToSign)
    {
        $error = 0;
        return (bool) bitcoinconsensus_verify_script(
            $scriptPubKey->getBinary(),
            $tx->getbinary(),
            $nInputToSign,
            $this->flags->getFlags(),
            $error
        );
    }
}
