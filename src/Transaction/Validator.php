<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\Consensus\ConsensusInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;

class Validator implements ValidatorInterface
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
        return $consensus->verify($this->transaction, $scriptPubKey, $nInput, $this->transaction->getWitness($nInput));
    }

    /**
     * @param ConsensusInterface $consensus
     * @param array $scriptPubKeys
     * @return bool
     */
    public function checkSignatures(ConsensusInterface $consensus, array $scriptPubKeys)
    {
        if (count($this->transaction->getInputs()) !== count($scriptPubKeys)) {
            throw new \InvalidArgumentException('Incorrect scriptPubKey count');
        }

        $result = true;
        foreach ($scriptPubKeys as $i => $scriptPubKey) {
            $result = $result && $this->checkSignature($consensus, $i, $scriptPubKey);
        }

        return $result;
    }
}
