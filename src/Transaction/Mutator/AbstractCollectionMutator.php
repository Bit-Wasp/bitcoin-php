<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Mutator;
use ArrayAccess;
use Countable;
use Iterator;
use SplFixedArray;

abstract class AbstractCollectionMutator implements Iterator, ArrayAccess, Countable
{
    private $array;
    private $position;

    public function __construct(int $size)
    {
        $this->array = new SplFixedArray($size);
        $this->position = 0;
    }

    public function all(): array
    {
        return $this->array->toArray();
    }

    public function isNull(): bool
    {
        return count($this->array) === 0;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->array[$this->position];
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
        return isset($this->array[$this->position]);
    }

    public function offsetExists($offset)
    {
        return isset($this->array[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->array[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (!$this->array->offsetExists($offset)) {
            throw new \OutOfRangeException('Nothing found at this offset');
        }

        $this->array[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new \InvalidArgumentException('Offset does not exist');
        }

        unset($this->array[$offset]);
    }

    public function count()
    {
        return $this->array->count();
    }
}
