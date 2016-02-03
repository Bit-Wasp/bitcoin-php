<?php

namespace BitWasp\Bitcoin\Collection;

abstract class StaticCollection implements CollectionInterface
{
    /**
     * @var \SplFixedArray
     */
    protected $set;

    /**
     * @return array
     */
    public function all()
    {
        return $this->set->toArray();
    }

    /**
     * @return bool
     */
    public function isNull()
    {
        return count($this->set) === 0;
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
    public function rewind()
    {
        $this->set->rewind();
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->set->current();
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->set->key();
    }

    /**
     *
     */
    public function next()
    {
        $this->set->next();
    }

    /**
     * @return bool
     */
    public function valid()
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
     * @return mixed
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
}
