<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionOutputSerializer;
use BitWasp\Buffertools\Buffer;

class TransactionOutput extends Serializable implements TransactionOutputInterface
{

    /**
     * @var string|int
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
    public function __construct($value, ScriptInterface $script)
    {
        $this->value = $value;
        $this->script = $script;
    }

    /**
     * Return the value of this output
     *
     * @return int|null
     */
    public function getValue()
    {
        return $this->value;
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
        return $this->script;
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
