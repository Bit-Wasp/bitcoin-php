<?php

namespace BitWasp\Bitcoin\Transaction;


use BitWasp\Bitcoin\Script\Consensus\ConsensusInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;

class Validator
{
    /**
     * @var TransactionInterface
     */
    private $transaction;

    /**
     * Validator constructor.
     * @param TransactionInterface $transaction
     */
    public function __construct(TransactionInterface $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * @param ConsensusInterface $consensus
     * @param int $nInput
     * @param ScriptInterface $scriptPubKey
     * @return bool
     */
    public function checkSignature(ConsensusInterface $consensus, $nInput, ScriptInterface $scriptPubKey)
    {
        return $consensus->verify($this->transaction, $scriptPubKey, $nInput);
    }

    /**
     * @param ConsensusInterface $consensus
     * @param ScriptInterface[] $scriptPubKeys
     * @return bool
     */
    public function checkSignatures(ConsensusInterface $consensus, array $scriptPubKeys)
    {
        $nInputs = count($this->transaction->getInputs());
        if ($nInputs !== count($scriptPubKeys)) {
            throw new \InvalidArgumentException('Incorrect scriptPubKey count');
        }

        $result = true;
        for ($i = 0; $i < $nInputs; $i++) {
            $result &= $this->checkSignature($consensus, $i, $scriptPubKeys[$i]);
        }

        return (bool) $result;
    }
}