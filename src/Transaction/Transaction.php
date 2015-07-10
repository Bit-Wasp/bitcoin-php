<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;

class Transaction extends AbstractTransaction implements TransactionInterface
{
    /**
     * @var Buffer|null
     */
    private $raw = null;

    /**
     * @var string|null
     */
    private $txId = null;

    /**
     * @param int|string                  $version
     * @param TransactionInputCollection  $inputs
     * @param TransactionOutputCollection $outputs
     * @param int|string                  $locktime
     * @param Buffer                      $raw
     * @throws \Exception
     */
    public function __construct(
        $version = TransactionInterface::DEFAULT_VERSION,
        TransactionInputCollection $inputs = null,
        TransactionOutputCollection $outputs = null,
        $locktime = '0',
        Buffer $raw = null
    ) {

        if (!is_numeric($version)) {
            throw new \InvalidArgumentException('Transaction version must be numeric');
        }

        if (!is_numeric($locktime)) {
            throw new \InvalidArgumentException('Transaction locktime must be numeric');
        }

        if (Bitcoin::getMath()->cmp($version, TransactionInterface::MAX_VERSION) > 0) {
            throw new \Exception('Version must be less than ' . TransactionInterface::MAX_VERSION);
        }

        $this->version = $version;
        $this->inputs = $inputs ?: new TransactionInputCollection();
        $this->outputs = $outputs ?: new TransactionOutputCollection();
        $this->locktime = $locktime;
        $this->raw = $raw;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        if ($this->txId === null) {
            $this->txId = $this->createTransactionId();
        }

        return $this->txId;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        if ($this->raw === null) {
            $this->raw = $this->createBuffer();
        }

        return $this->raw;
    }
}
