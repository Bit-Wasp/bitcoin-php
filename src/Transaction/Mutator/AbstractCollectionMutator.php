<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Mutator;
use ArrayAccess;
use Countable;
use Iterator;
use SplFixedArray;

abstract class AbstractCollectionMutator implements Iterator, ArrayAccess, Countable
{
    protected $set;
    private $position;

    public function __construct(int $size)
    {
        $this->set = new SplFixedArray($size);
        $this->position = 0;
    }

    public function all(): array
    {
        return $this->set->toArray();
    }

    public function isNull(): bool
    {
        return count($this->set) === 0;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->set[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        $this->position++;
    }

    public function valid()
    {
        return isset($this->set[$this->position]);
    }

    public function offsetExists($offset)
    {
        return isset($this->set[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->set[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (!$this->set->offsetExists($offset)) {
            throw new \OutOfRangeException('Nothing found at this offset');
        }

        $this->set[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new \InvalidArgumentException('Offset does not exist');
        }

        unset($this->set[$offset]);
    }

    public function count()
    {
        return $this->set->count();
    }
}
