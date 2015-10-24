<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionOutputSerializer;

class TransactionOutput extends Serializable implements TransactionOutputInterface
{
    /**
     * @var string|int
     */
    private $value;

    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * Initialize class
     *
     * @param int|string $value
     * @param ScriptInterface $script
     */
    public function __construct($value, ScriptInterface $script)
    {
        $this->value = $value;
        $this->script = $script;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->script = clone $this->script;
    }

    /**
     * @see TransactionOutputInterface::getValue()
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @see TransactionOutputInterface::getScript()
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     * @see \BitWasp\Bitcoin\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        return (new TransactionOutputSerializer())->serialize($this);
    }
}
