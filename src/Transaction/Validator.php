<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\Consensus\ConsensusInterface;

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
     * @param int $flags
     * @param int $nInput
     * @param TransactionOutputInterface $output
     * @return bool
     */
    public function checkSignature(ConsensusInterface $consensus, $flags, $nInput, TransactionOutputInterface $output)
    {
        $witnesses = $this->transaction->getWitnesses();
        $witness = isset($witnesses[$nInput]) ? $witnesses[$nInput] : null;
        return $consensus->verify($this->transaction, $output->getScript(), $flags, $nInput, $output->getValue(), $witness);
    }

    /**
     * @param ConsensusInterface $consensus
     * @param int $flags
     * @param TransactionOutputInterface[] $outputs
     * @return bool
     */
    public function checkSignatures(ConsensusInterface $consensus, $flags, array $outputs)
    {
        if (count($this->transaction->getInputs()) !== count($outputs)) {
            throw new \InvalidArgumentException('Incorrect scriptPubKey count');
        }

        $result = true;
        foreach ($outputs as $i => $txOut) {
            $result = $result && $this->checkSignature($consensus, $flags, $i, $txOut);
        }

        return $result;
    }
}
