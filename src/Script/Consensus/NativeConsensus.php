<?php

namespace BitWasp\Bitcoin\Script\Consensus;

use BitWasp\Bitcoin\Script\Interpreter\InterpreterFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class NativeConsensus implements ConsensusInterface
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
        $inputs = $tx->getInputs();
        return $this->factory->create($tx)
            ->verify(
                $inputs[$nInputToSign]->getScript(),
                $scriptPubKey,
                $nInputToSign
            );
    }
}
