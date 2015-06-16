<?php

namespace BitWasp\Bitcoin\Script\Consensus;

use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class NativeConsensus
{
    /**
     * @var InterpreterFactory
     */
    private $factory;

    /**
     * @param InterpreterFactory $factory
     */
    public function __construct(InterpreterFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param TransactionInterface $tx
     * @param ScriptInterface $scriptPubKey
     * @param int $nInputToSign
     * @return bool
     */
    public function verify(TransactionInterface $tx, ScriptInterface $scriptPubKey, $nInputToSign)
    {
        return $this->factory->create($tx)
            ->verify(
                $tx->getInputs()->getInput($nInputToSign)->getScript(),
                $scriptPubKey,
                $nInputToSign
            );
    }
}
