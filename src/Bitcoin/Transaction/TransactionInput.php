<?php

namespace Afk11\Bitcoin\Transaction;

use \Afk11\Bitcoin\Bitcoin;
use \Afk11\Bitcoin\Buffer;
use \Afk11\Bitcoin\Parser;
use \Afk11\Bitcoin\Script\Script;
use Afk11\Bitcoin\Script\ScriptInterface;
use \Afk11\Bitcoin\SerializableInterface;
use Afk11\Bitcoin\Serializer\Transaction\TransactionInputSerializer;

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
     * @var int
     */
    protected $sequence;

    /**
     * @var ScriptInterface
     */
    protected $script;

    /**
     * @var Buffer
     */
    protected $scriptBuf;

    /**
     * @param null $txid
     * @param null $vout
     * @param ScriptInterface|Buffer $script
     * @param int $sequence
     */
    public function __construct($txid = null, $vout = null, $script = null, $sequence = null)
    {
        $this->txid = $txid;
        $this->vout = $vout;

        if (!is_null($script)) {
            if ($script instanceof ScriptInterface) {
                $this->setScript($script);
            } elseif ($script instanceof Buffer) {
                $this->setScriptBuf($script);
            }
        }

        $this->sequence = $sequence;

        return $this;
    }

    /**
     * Return the transaction ID buffer
     *
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
        $this->txid = $txid;
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
        $this->vout = $vout;
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
        $this->sequence = $sequence;
        return $this;
    }

    /**
     * Get Script Buffer - just return the buffer, not the script
     * @return \Afk11\Bitcoin\Buffer
     */
    public function getScriptBuf()
    {
        if ($this->scriptBuf == null) {
            return new Buffer();
        }
        return $this->scriptBuf;
    }

    /**
     * Set Script Buffer
     * @param \Afk11\Bitcoin\Buffer $script
     * @return $this
     */
    public function setScriptBuf(Buffer $script)
    {
        $this->scriptBuf = $script;
        return $this;
    }

    /**
     * Return an initialized script. Checks if already has a script
     * object. If not, returns script from scriptBuf (which can simply
     * be null).
     *
     * @return Script
     */
    public function getScript()
    {
        if ($this->script == null) {
            $this->script = new Script();
            $this->script->set($this->getScriptBuf());
        }
        return $this->script;
    }

    /**
     * Set a Script
     *
     * @param ScriptInterface $script
     * @return $this
     */
    public function setScript(ScriptInterface $script)
    {
        $this->script = $script;
        return $this;
    }

    /**
     * Check whether this transaction is a coinbase transaction
     *
     * @return boolean
     */
    public function isCoinbase()
    {
        return $this->getTransactionId() == '0000000000000000000000000000000000000000000000000000000000000000'
            && Bitcoin::getMath()->cmp($this->getVout(), Bitcoin::getMath()->hexDec('ffffffff')) == '0';
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        $serializer = new TransactionInputSerializer();
        $out = $serializer->serialize($this);
        return $out;
    }
}
