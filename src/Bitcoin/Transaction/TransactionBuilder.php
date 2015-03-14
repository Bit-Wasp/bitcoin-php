<?php

namespace Afk11\Bitcoin\Transaction;

class TransactionBuilder
{
    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @param TransactionInterface $tx
     */
    public function __construct(TransactionInterface $tx = null)
    {
        $tx = $tx ?: TransactionFactory::create();
        $this->transaction = $tx;
    }

    /**
     * @param TransactionInterface $tx
     * @param $outputToSpend
     * @return $this
     */
    public function spendOutput(TransactionInterface $tx, $outputToSpend)
    {
        $output = $tx->getOutputs()->getOutput($outputToSpend);

        $input = (new TransactionInput())
            ->setTransactionId($tx->getTransactionId())
            ->setVout($outputToSpend)
            ->setOutputScript($output->getScript());

        $this->transaction->getInputs()->addInput($input);

        return $this;
    }

    /**
     * @return Transaction
     */
    public function getTransaction()
    {
        return $this->transaction;
    }
}
