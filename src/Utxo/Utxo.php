<?php

namespace BitWasp\Bitcoin\Utxo;

use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class Utxo
{
    /**
     * @var string
     */
    private $hashPrevOut;

    /**
     * @var int|string
     */
    private $nPrevOut;

    /**
     * @var TransactionOutputInterface
     */
    private $prevOut;

    /**
     * @param string $hashPrevOut
     * @param int|string $nPrevOut
     * @param TransactionOutputInterface $prevOut
     */
    public function __construct($hashPrevOut, $nPrevOut, TransactionOutputInterface $prevOut)
    {
        $this->hashPrevOut = $hashPrevOut;
        $this->nPrevOut = $nPrevOut;
        $this->prevOut = $prevOut;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->hashPrevOut;
    }

    /**
     * @return int|string
     */
    public function getVout()
    {
        return $this->nPrevOut;
    }

    /**
     * @return TransactionOutputInterface
     */
    public function getOutput()
    {
        return $this->prevOut;
    }
}
