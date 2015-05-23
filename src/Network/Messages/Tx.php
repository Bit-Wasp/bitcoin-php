<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class Tx extends NetworkSerializable
{
    /**
     * @var TransactionInterface
     */
    private $transaction;

    /**
     * @param TransactionInterface $tx
     */
    public function __construct(TransactionInterface $tx)
    {
        $this->transaction = $tx;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Network\NetworkSerializableInterface::getNetworkCommand()
     */
    public function getNetworkCommand()
    {
        return 'tx';
    }

    /**
     * @return TransactionInterface
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        return $this->transaction->getBuffer();
    }
}
