<?php

namespace BitWasp\Bitcoin\Script\Consensus;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Script\Interpreter\Checker;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\PrecomputedData;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionOutputSerializer;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class NativeConsensus implements ConsensusInterface
{
    /**
     * @var EcAdapterInterface
     */
    private $adapter;
    private $outPointSerializer;
    private $txOutSerializer;

    /**
     * NativeConsensus constructor.
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(EcAdapterInterface $ecAdapter = null)
    {
        $this->adapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $this->outPointSerializer = new OutPointSerializer();
        $this->txOutSerializer = new TransactionOutputSerializer();
    }

    /**
     * @param TransactionInterface $tx
     * @param ScriptInterface $scriptPubKey
     * @param int $nInputToSign
     * @param int $flags
     * @param int $amount
     * @return bool
     */
    public function verify(TransactionInterface $tx, ScriptInterface $scriptPubKey, int $flags, int $nInputToSign, int $amount, array $spentTxOuts = null): bool
    {
        $inputs = $tx->getInputs();
        $interpreter = new Interpreter($this->adapter);
        $checker = new Checker($this->adapter, $tx, $nInputToSign, $amount);
        if (null !== $spentTxOuts) {
            $precomputed = new PrecomputedData($this->outPointSerializer, $this->txOutSerializer);
            $precomputed->init($tx, $spentTxOuts);
            $checker->setPrecomputedData($precomputed);
        }
        $wit = null;
        if (array_key_exists($nInputToSign,  $tx->getWitnesses())) {
            $wit = $tx->getWitness($nInputToSign);
        }
        return $interpreter->verify(
            $inputs[$nInputToSign]->getScript(),
            $scriptPubKey,
            $flags,
            $checker,
            $wit
        );
    }
}
