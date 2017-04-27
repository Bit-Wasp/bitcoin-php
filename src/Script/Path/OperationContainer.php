<?php

namespace BitWasp\Bitcoin\Script\Path;

use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\ScriptFactory;

class OperationContainer implements \ArrayAccess, \Countable
{
    /**
     * @var Operation[]
     */
    private $container;

    /**
     * @var int
     */
    private $count;

    /**
     * OperationContainer constructor.
     * @param array $operations
     */
    public function __construct(array $operations = []) {
        foreach ($operations as $operation) {
            if (!($operation instanceof Operation)) {
                throw new \InvalidArgumentException("Invalid argument - array of Operations required");
            }
        }

        $this->container = $operations;
        $this->count = count($operations);
    }

    /**
     * @return \BitWasp\Bitcoin\Script\ScriptInterface
     */
    public function makeScript()
    {
        return ScriptFactory::fromOperations($this->container);
    }

    /**
     * @return bool
     */
    public function isLoneLogicalOp()
    {
        return $this->count === 1 && $this->container[0]->isLogical();
    }

    /**
     * @return array|Operation[]
     */
    public function all() {
        return $this->container;
    }

    /**
     * @return int
     */
    public function count() {
        return $this->count;
    }

    /**
     * @param int $offset
     * @param Operation $value
     */
    public function offsetSet($offset, $value) {
        throw new \RuntimeException("Not implemented");
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->container[$offset]);
    }

    /**
     * @param int $offset
     */
    public function offsetUnset($offset) {
        unset($this->container[$offset]);
    }

    /**
     * @param int $offset
     * @return Operation|null
     */
    public function offsetGet($offset) {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }
}