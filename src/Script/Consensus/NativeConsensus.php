<?php

namespace BitWasp\Bitcoin\Script\Consensus;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Flags;
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
     * @var Flags
     */
    private $flags;

    /**
     * NativeConsensus constructor.
     * @param EcAdapterInterface $ecAdapter
     * @param Flags $flags
     */
    public function __construct(EcAdapterInterface $ecAdapter, Flags $flags)
    {
        $this->adapter = $ecAdapter;
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
        $inputs = $tx->getInputs();
        $interpreter = new Interpreter($this->adapter, $tx, $this->flags);
        return $interpreter->verify(
            $inputs[$nInputToSign]->getScript(),
            $scriptPubKey,
            $nInputToSign
        );
    }
}
