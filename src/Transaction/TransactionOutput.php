<?php

namespace Afk11\Bitcoin\Transaction;

use Afk11\Bitcoin\Script\Script;
use Afk11\Bitcoin\Script\ScriptInterface;
use Afk11\Bitcoin\Serializer\Transaction\TransactionOutputSerializer;
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
     * @var ScriptInterface
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
            if ($script instanceof ScriptInterface) {
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
     * @return ScriptInterface
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
     * @param ScriptInterface $script
     * @return $this
     */
    public function setScript(ScriptInterface $script)
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

    public function getBuffer()
    {
        $serializer = new TransactionOutputSerializer();
        $out = $serializer->serialize($this);
        return $out;
    }
}
