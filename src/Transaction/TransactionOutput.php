<?php

namespace Afk11\Bitcoin\Transaction;

use Afk11\Bitcoin\Script\Script;
use Afk11\Bitcoin\Util\Math;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\SerializableInterface;

class TransactionOutput implements TransactionOutputInterface, SerializableInterface
{

    /**
     * @var \Afk11\Bitcoin\Buffer
     */
    protected $value;

    /**
     * @var Script
     */
    protected $script;

    /**
     * @var \Afk11\Bitcoin\Buffer
     */
    protected $scriptBuf;

    /**
     * Initialize class
     *
     * @param null $script
     * @param int|string|null $value
     */
    public function __construct($script = null, $value = null)
    {
        if (!is_null($script)) {
            if ($script instanceof Script) {
                $this->setScript($script);
            } elseif ($script instanceof Buffer) {
                $this->setScriptBuf($script);
            }
        }

        $this->value = $value;

        return $this;
    }

    /**
     * Return the value of this output
     *
     * @return int|null
     */
    public function getValue()
    {
        if ($this->value == null) {
            return '0';
        }

        return $this->value;
    }

    /**
     * Set the value of this output, in satoshis
     *
     * @param int|null $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
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
            $this->script = new Script($this->getScriptBuf());
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
     * Return the current script buffer
     *
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
     *
     * @param \Afk11\Bitcoin\Buffer $buffer
     * @return $this
     */
    public function setScriptBuf(Buffer $buffer)
    {
        $this->scriptBuf = $buffer;
        return $this;
    }

    /**
     * From a Parser instance, load what should be the script data.
     *
     * @param $parser
     * @return $this
     */
    public function fromParser(Parser &$parser)
    {
        $this
            ->setValue(
                $parser
                    ->readBytes(8, true)
                    ->serialize('int')
            )
            ->setScriptBuf(
                $parser->getVarString()
            );
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function serialize($type = null)
    {
        $parser = new Parser;
        $parser
            ->writeInt(8, $this->getValue(), true)
            ->writeWithLength(
                new Buffer($this->getScript()->serialize())
            );

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
