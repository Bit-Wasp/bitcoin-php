<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/11/14
 * Time: 05:09
 */

namespace Bitcoin;

class TransactionInput implements TransactionInputInterface {

    protected $txid;
    protected $vout;
    protected $sequence;
    protected $script;

    public function getTransactionId()
    {
        return $this->txid;
    }

    public function setTransactionId($txid)
    {
        if (ctype_xdigit($txid) == true and strlen($txid) == 64) {
            $this->txid = $txid;
        }

        return $this;
    }

    public function getVout()
    {
        return $this->vout;
    }

    public function setVout($vout)
    {
        if (is_numeric($vout) == true) {
            $this->vout = $vout;
        }

        return $this;
    }

    public function getSequence()
    {
        if ($this->sequence == null) {
            return self::DEFAULT_SEQUENCE;
        }

        return $this->sequence;
    }

    public function setSequence($sequence)
    {
        if (is_numeric($sequence) == true) {
            $this->sequence = $sequence;
        }

        return $this;
    }

    public function getScript()
    {
        return $this->script;
    }

    public function setScript()
    {
        // TODO
    }

    public function isCoinbase()
    {

    }
    public function serialize()
    {

    }
} 