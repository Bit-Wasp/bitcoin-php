<?php

namespace Bitcoin;

/**
 * Class TransactionInput
 * @package Bitcoin
 */
class TransactionInput implements TransactionInputInterface
{
    /**
     * @var
     */
    protected $txid;
    /**
     * @var
     */
    protected $vout;
    /**
     * @var
     */
    protected $sequence;
    /**
     * @var
     */
    protected $script;

    /**
     * @return mixed
     */
    public function getTransactionId()
    {
        return $this->txid;
    }

    /**
     * @param $txid
     * @return $this
     */
    public function setTransactionId($txid)
    {
        if (ctype_xdigit($txid) == true and strlen($txid) == 64) {
            $this->txid = $txid;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVout()
    {
        return $this->vout;
    }

    /**
     * @param $vout
     * @return $this
     */
    public function setVout($vout)
    {
        if (is_numeric($vout) == true) {
            $this->vout = $vout;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getSequence()
    {
        if ($this->sequence == null) {
            return self::DEFAULT_SEQUENCE;
        }

        return $this->sequence;
    }

    /**
     * @param $sequence
     * @return $this
     */
    public function setSequence($sequence)
    {
        if (is_numeric($sequence) == true) {
            $this->sequence = $sequence;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     *
     */
    public function setScript()
    {
        // TODO
    }

    /**
     *
     */
    public function isCoinbase()
    {

    }

    /**
     *
     */
    public function serialize()
    {

    }
}
