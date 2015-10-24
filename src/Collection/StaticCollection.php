<?php

namespace BitWasp\Bitcoin\Collection;

use BitWasp\Bitcoin\Collection\CollectionInterface;

abstract class StaticCollection implements CollectionInterface
{
    /**
     * @var \SplFixedArray
     */
    protected $set;

    /**
     * @param array $values
     */
    abstract public function __construct(array $values);

    /**
     * @return array
     */
    public function all()
    {
        return $this->set->toArray();
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->set->count();
    }

    /**
     *
     */
    function rewind()
    {
        $this->set->rewind();
    }

    /**
     * @return array
     */
    function current()
    {
        return $this->set->current();
    }

    /**
     * @return int
     */
    function key()
    {
        return $this->set->key();
    }

    /**
     *
     */
    function next()
    {
        $this->set->next();
    }

    /**
     * @return bool
     */
    function valid()
    {
        return $this->set->valid();
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->set->offsetExists($offset);
    }

    /**
     * @param int $offset
     */
    public function offsetUnset($offset)
    {
        throw new \RuntimeException('Cannot unset from a Static Collection');
    }

    /**
     * @param int $offset
     * @return array
     */
    public function offsetGet($offset)
    {
        if (!$this->set->offsetExists($offset)) {
            throw new \OutOfRangeException('Nothing found at this offset');
        }
        return $this->set->offsetGet($offset);
    }

    /**
     * @param int $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('Cannot add to a Static Collection');
    }

    /**
     * @param int $index
     * @return array
     */
    public function get($index)
    {
        return $this->offsetGet($index);
    }
}
