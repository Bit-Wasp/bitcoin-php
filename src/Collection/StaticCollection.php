<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Collection;

use BitWasp\Buffertools\BufferInterface;

/**
 * @deprecated v2.0.0
 */
abstract class StaticCollection implements CollectionInterface
{
    /**
     * @var array
     */
    protected $set;

    /**
     * @var int
     */
    protected $position = 0;

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->set;
    }

    /**
     * @param int $start
     * @param int $length
     * @return self
     */
    public function slice(int $start, int $length)
    {
        $end = count($this->set);
        if ($start > $end || $length > $end) {
            throw new \RuntimeException('Invalid start or length');
        }

        $sliced = array_slice($this->set, $start, $length);
        return new static(...$sliced);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->set);
    }

    /**
     * @return BufferInterface
     */
    public function bottom(): BufferInterface
    {
        if (count($this->set) === 0) {
            throw new \RuntimeException('No bottom for empty collection');
        }
        
        return $this->offsetGet(count($this) - 1);
    }

    /**
     * @return BufferInterface
     */
    public function top(): BufferInterface
    {
        if (count($this->set) === 0) {
            throw new \RuntimeException('No top for empty collection');
        }

        return $this->offsetGet(0);
    }

    /**
     * @return bool
     */
    public function isNull(): bool
    {
        return count($this->set) === 0;
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @return BufferInterface
     */
    public function current(): BufferInterface
    {
        return $this->set[$this->position];
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * @return void
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->set[$this->position]);
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->set);
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
        throw new \RuntimeException('Cannot add to a Static Collection');
    }
}
