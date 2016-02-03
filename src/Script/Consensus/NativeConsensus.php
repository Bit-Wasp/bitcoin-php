<?php

namespace BitWasp\Bitcoin\Script\Consensus;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Script\Interpreter\Checker;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class NativeConsensus implements ConsensusInterface
{
    /**
     * @var EcAdapterInterface
     */
    private $adapter;

    /**
     * @var int
     */
    private $flags;

    /**
     * NativeConsensus constructor.
     * @param EcAdapterInterface $ecAdapter
     * @param int $flags
     */
    public function __construct(EcAdapterInterface $ecAdapter, $flags)
    {
        $this->adapter = $ecAdapter;
        $this->flags = $flags;
    }

    /**
     * @param TransactionInterface $tx
     * @param ScriptInterface $scriptPubKey
     * @param int $nInputToSign
     * @param ScriptWitness|null $witness
     * @return bool
     */
    public function verify(TransactionInterface $tx, ScriptInterface $scriptPubKey, $nInputToSign, $amount, ScriptWitness $witness = null)
    {
        $inputs = $tx->getInputs();
        $interpreter = new Interpreter($this->adapter, $tx);
        return $interpreter->verify(
            $inputs[$nInputToSign]->getScript(),
            $scriptPubKey,
            $this->flags,
            new Checker($this->adapter, $tx, $nInputToSign, $amount),
            $witness
        );
    }
}
