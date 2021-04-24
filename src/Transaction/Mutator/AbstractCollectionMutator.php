<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Mutator;

abstract class AbstractCollectionMutator implements \Iterator, \ArrayAccess, \Countable
{
    /**
     * @var array
     */
    protected $set = [];

    private $position = 0;

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->set;
    }

    /**
     * @return bool
     */
    public function isNull(): bool
    {
        return count($this->set) === 0;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->set);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->set[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return array_key_exists($this->position, $this->set);
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->set);
    }

    /**
     * @param int $offset
     */
    public function offsetUnset($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new \InvalidArgumentException('Offset does not exist');
        }

        $this->set = array_slice($this->set, 0, $offset - 1) + array_slice($this->set, $offset + 1);
    }

    /**
     * @param int $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if (!array_key_exists($offset, $this->set)) {
            throw new \OutOfRangeException('Nothing found at this offset');
        }
        return $this->set[$offset];
    }

    /**
     * @param int $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if ($offset > count($this->set)) {
            throw new \InvalidArgumentException();
        }
        $this->set[$offset] = $value;
    }
}
