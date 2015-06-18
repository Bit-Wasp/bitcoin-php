<?php

namespace BitWasp\Bitcoin\Utxo;

use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class Utxo
{
    /**
     * @var string
     */
    private $txid;

    /**
     * @var int|string
     */
    private $vout;

    /**
     * @var TransactionOutputInterface
     */
    private $txOut;

    /**
     * @param string $txid
     * @param int|string $vout
     * @param TransactionOutputInterface $txOut
     */
    public function __construct($txid, $vout, TransactionOutputInterface $txOut)
    {
        $this->txid = $txid;
        $this->vout = $vout;
        $this->txOut = $txOut;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->txid;
    }

    /**
     * @return int|string
     */
    public function getVout()
    {
        return $this->vout;
    }

    /**
     * @return TransactionOutputInterface
     */
    public function getOutput()
    {
        return $this->txOut;
    }
}
