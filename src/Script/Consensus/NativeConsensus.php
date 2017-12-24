<?php

namespace BitWasp\Bitcoin\Script\Consensus;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Script\Interpreter\Checker;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class NativeConsensus implements ConsensusInterface
{
    /**
     * @var EcAdapterInterface
     */
    private $adapter;

    /**
     * NativeConsensus constructor.
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(EcAdapterInterface $ecAdapter = null)
    {
        $this->adapter = $ecAdapter ?: Bitcoin::getEcAdapter();
    }

    /**
     * @param TransactionInterface $tx
     * @param ScriptInterface $scriptPubKey
     * @param int $nInputToSign
     * @param int $flags
     * @param int $amount
     * @return bool
     */
    public function verify(TransactionInterface $tx, ScriptInterface $scriptPubKey, int $flags, int $nInputToSign, int $amount): bool
    {
        $inputs = $tx->getInputs();
        $interpreter = new Interpreter($this->adapter);
        return $interpreter->verify(
            $inputs[$nInputToSign]->getScript(),
            $scriptPubKey,
            $flags,
            new Checker($this->adapter, $tx, $nInputToSign, $amount),
            isset($tx->getWitnesses()[$nInputToSign]) ? $tx->getWitness($nInputToSign) : null
        );
    }
}
