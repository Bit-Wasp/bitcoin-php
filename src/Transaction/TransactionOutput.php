<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionOutputSerializer;
use BitWasp\CommonTrait\FunctionAliasArrayAccess;

class TransactionOutput extends Serializable implements TransactionOutputInterface
{
    use FunctionAliasArrayAccess;

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
     * @param int $value
     * @param ScriptInterface $script
     */
    public function __construct($value, ScriptInterface $script)
    {

        $this->value = $value;
        $this->script = $script;
        $this
            ->initFunctionAlias('value', 'getValue')
            ->initFunctionAlias('script', 'getScript');
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
     * @param TransactionOutputInterface $output
     * @return bool
     */
    public function equals(TransactionOutputInterface $output)
    {
        $script = $this->script->equals($output->getScript());
        if (!$script) {
            return false;
        }

        return gmp_cmp($this->value, $output->getValue()) === 0;
    }

    /**
     * @see \BitWasp\Bitcoin\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        return (new TransactionOutputSerializer())->serialize($this);
    }
}
