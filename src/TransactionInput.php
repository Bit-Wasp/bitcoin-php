<?php

namespace Bitcoin;

use Bitcoin\Util\Buffer;
use Bitcoin\Util\Parser;
use Bitcoin\Script;

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

    public function fromParser(Parser &$parser)
    {
        $this->setTransactionId(
            $parser->readBytes(32, true)
        )
        ->setVout(
            $parser->readBytes(4)
        );

        $this->setScriptBuf(
            $parser->readBytes(
                $parser
                    ->getVarInt()
                    ->serialize('int')
            )
        );
        $this->setSequence($parser->readBytes(4));

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
        return (new Parser)
            ->writeBytes(32, $this->getTransactionId())
            ->writeInt(4, $this->getVout()->serialize('int'), true)
            ->writeWithLength(
                new Buffer($this->getScript()->serialize())
            )
            ->getBuffer()
            ->serialize($type);
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
    public function setTransactionId(Buffer $txid)
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
    public function setVout(Buffer $vout)
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
    public function setSequence(Buffer $sequence)
    {
        $this->sequence = $sequence;

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
     *
     */
    public function isCoinbase()
    {
        return $this->getTransactionId()->serialize('hex') == '0000000000000000000000000000000000000000000000000000000000000000';
    }
}
