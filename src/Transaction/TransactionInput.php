<?php

namespace Bitcoin\Transaction;

use Bitcoin\Util\Buffer;
use Bitcoin\Util\Parser;
use Bitcoin\Script\Script;
use Bitcoin\SerializableInterface;

/**
 * Class TransactionInput
 * @package Bitcoin
 */
class TransactionInput implements TransactionInputInterface, SerializableInterface
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
     * @var Script
     */
    protected $script;

    /**
     * @var Buffer
     */
    protected $scriptBuf;


    public function __construct()
    {
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
     * @return Buffer
     */
    public function getScriptBuf()
    {
        return $this->scriptBuf;
    }

    /**
     * Set Script Buffer
     * @param Buffer $script
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
     * @param Script $script
     * @return $this
     */
    public function setScript(Script $script)
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
        return $this->getTransactionId() == '0000000000000000000000000000000000000000000000000000000000000000';
    }

    /**
     * Set all parameters when given a parser at the start of the input
     *
     * @param Parser $parser
     * @return $this
     * @throws \Exception
     */
    public function fromParser(Parser &$parser)
    {
        $this
            ->setTransactionId(
                $parser
                    ->readBytes(32, true)
                    ->serialize('hex')
            )
            ->setVout(
                $parser
                    ->readBytes(4)
                    ->serialize('int')
            )
            ->setScriptBuf(
                $parser->getVarString()
            )
            ->setSequence(
                $parser
                    ->readBytes(4)
                    ->serialize('int')
            );

        return $this;
    }

    /**
     * Serialize the transaction input.
     *
     * @param $type
     * @return string
     */
    public function serialize($type = null)
    {
        $parser = new Parser;
        $parser
            ->writeBytes(32, $this->getTransactionId(), true)
            ->writeInt(4, $this->getVout())
            ->writeWithLength(
                new Buffer($this->getScript()->serialize())
            )
            ->writeInt(4, $this->getSequence(), true);

        return $parser
            ->getBuffer()
            ->serialize($type);
    }

    /**
     * @inheritdoc
     */
    public function getSize($type = null)
    {
        return strlen($this->serialize($type));
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return $this->serialize('hex');
    }
}
