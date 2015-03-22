<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionOutputSerializer;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\SerializableInterface;

class TransactionOutput implements TransactionOutputInterface, SerializableInterface
{

    /**
     * @var \BitWasp\Bitcoin\Buffer
     */
    protected $value;

    /**
     * @var ScriptInterface
     */
    protected $script;

    /**
     * Initialize class
     *
     * @param ScriptInterface $script
     * @param int|string|null $value
     */
    public function __construct($value = null, ScriptInterface $script = null)
    {
        if ($script !== null) {
            $this->setScript($script);
        }
        $this->value = $value;
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
            $this->script = new Script();
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
     * @return Buffer
     */
    public function getBuffer()
    {
        $serializer = new TransactionOutputSerializer();
        $out = $serializer->serialize($this);
        return $out;
    }
}
