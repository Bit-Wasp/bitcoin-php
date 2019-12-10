<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Collection\StaticBufferCollection;
use BitWasp\Bitcoin\Serializer\Script\ScriptWitnessSerializer;
use BitWasp\Buffertools\BufferInterface;

class ScriptWitness implements ScriptWitnessInterface
{
    /**
     * @var BufferInterface[]
     */
    protected $set = [];

    /**
     * @var int
     */
    protected $position = 0;

    /**
     * StaticBufferCollection constructor.
     * @param BufferInterface ...$values
     */
    public function __construct(BufferInterface... $values)
    {
        $this->set = $values;
    }

    /**
     * @param ScriptWitnessInterface $witness
     * @return bool
     */
    public function equals(ScriptWitnessInterface $witness): bool
    {
        $nStack = count($this);
        if ($nStack !== count($witness)) {
            return false;
        }

        for ($i = 0; $i < $nStack; $i++) {
            if (false === $this->offsetGet($i)->equals($witness->offsetGet($i))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer(): BufferInterface
    {
        return (new ScriptWitnessSerializer())->serialize($this);
    }

    /**
     * @return BufferInterface[]
     */
    public function all(): array
    {
        return $this->set;
    }

    /**
     * @param int $start
     * @param int $length
     * @return ScriptWitnessInterface
     */
    public function slice(int $start, int $length): ScriptWitnessInterface
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
        throw new \RuntimeException('ScriptWitness is immutable');
    }

    /**
     * @param int $offset
     * @return BufferInterface
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
        throw new \RuntimeException('ScriptWitness is immutable');
    }
}
