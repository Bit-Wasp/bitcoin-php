<?php

namespace BitWasp\Bitcoin\Utxo;

use BitWasp\Bitcoin\Transaction\TransactionOutput;

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
     * @var TransactionOutput
     */
    private $txOut;

    /**
     * @param string $txid
     * @param int|string $vout
     * @param TransactionOutput $txOut
     */
    public function __construct($txid, $vout, TransactionOutput $txOut)
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
     * @return TransactionOutput
     */
    public function getOutput()
    {
        return $this->txOut;
    }
}
