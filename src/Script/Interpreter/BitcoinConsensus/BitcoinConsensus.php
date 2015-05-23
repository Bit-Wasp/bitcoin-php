<?php

namespace BitWasp\Bitcoin\Script\Interpreter\BitcoinConsensus;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class BitcoinConsensus implements InterpreterInterface
{
    /**
     * @var \BitWasp\Bitcoin\Flags
     */
    private $flags;

    /**
     * @var TransactionInterface
     */
    private $transaction;

    /**
     * @param TransactionInterface $transaction
     * @param Flags $flags
     */
    public function __construct(TransactionInterface $transaction, Flags $flags)
    {
        $this->flags = $flags;
        $this->transaction = $transaction;
    }

    /**
     * @param ScriptInterface $scriptSig
     * @param ScriptInterface $scriptPubKey
     * @param $nInputToSign
     * @return bool
     */
    public function verify(ScriptInterface $scriptSig, ScriptInterface $scriptPubKey, $nInputToSign)
    {
        $tx = $this->transaction->makeCopy();
        $tx->getInputs()->getInput($nInputToSign)->setScript($scriptSig);

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
