<?php

namespace BitWasp\Bitcoin\Script\Path;

class PathTrace implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * @var OperationContainer[]
     */
    private $container;

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var int
     */
    private $count = 0;

    /**
     * PathTrace constructor.
     * @param OperationContainer[] $opsLists
     */
    public function __construct(array $opsLists)
    {
        foreach ($opsLists as $opList) {
            if (!($opList instanceof OperationContainer)) {
                throw new \InvalidArgumentException("Invalid argument: Array of OperationContainer required");
            }
        }
        $this->container = $opsLists;
        $this->count = count($opsLists);
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     *
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @return OperationContainer
     */
    public function current()
    {
        return $this->container[$this->position];
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return isset($this->container[$this->position]);
    }

    /**
     * @param int $offset
     * @param OperationContainer $value
     */
    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException("Not implemented");
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * @param int $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * @param int $offset
     * @return OperationContainer|null
     */
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }
}
