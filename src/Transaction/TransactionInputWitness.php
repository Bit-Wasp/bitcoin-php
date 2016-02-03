<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\BufferInterface;

class TransactionInputWitness implements TransactionInputWitnessInterface
{
    /**
     * @var BufferInterface[]
     */
    private $values = [];

    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * TransactionInputWitness constructor.
     * @param ScriptInterface $script
     */
    public function __construct(ScriptInterface $script)
    {
        array_map(function (Operation $value) {
            if (!$value->isPush()) {
                throw new \InvalidArgumentException('Script must only contain push ops');
            }

            $this->values[] = $value->getData();
        }, $script->getScriptParser()->decode());

        $this->script = $script;
    }

    /**
     * @return ScriptInterface
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     * @return BufferInterface[]
     */
    public function getStack()
    {
        return $this->values;
    }

    /**
     * @return bool
     */
    public function isNull()
    {
        return count($this->values) === 0;
    }
}
