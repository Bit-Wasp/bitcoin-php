<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Collection\StaticCollection;
use BitWasp\Bitcoin\Serializer\Script\ScriptWitnessSerializer;
use BitWasp\Buffertools\BufferInterface;

class ScriptWitness extends StaticCollection implements ScriptWitnessInterface
{
    /**
     * ScriptWitness constructor.
     * @param BufferInterface[] $sigValues
     */
    public function __construct(array $sigValues)
    {
        $this->set = new \SplFixedArray(count($sigValues));
        foreach ($sigValues as $idx => $push) {
            if (!$push instanceof BufferInterface) {
                throw new \InvalidArgumentException('Must provide BufferInterface[] to ScriptWitness');
            }
            $this->set->offsetSet($idx, $push);
        }
    }

    public function __clone()
    {
        $outputs = $this->set;
        $this->set = new \SplFixedArray(count($outputs));

        foreach ($outputs as $idx => $output) {
            $this->set->offsetSet($idx, $output);
        }
    }

    /**
     * @return BufferInterface
     */
    public function bottom()
    {
        return parent::offsetGet(count($this) - 1);
    }

    /**
     * @return BufferInterface
     */
    public function current()
    {
        return $this->set->current();
    }

    /**
     * @param int $offset
     * @return BufferInterface
     */
    public function offsetGet($offset)
    {
        if (!$this->set->offsetExists($offset)) {
            throw new \OutOfRangeException('No offset found');
        }

        return $this->set->offsetGet($offset);
    }

    /**
     * @param int $start
     * @param int $length
     * @return ScriptWitness
     */
    public function slice($start, $length)
    {
        $end = $this->set->getSize();
        if ($start > $end || $length > $end) {
            throw new \RuntimeException('Invalid start or length');
        }

        $sliced = array_slice($this->set->toArray(), $start, $length);
        return new self($sliced);
    }

    /**
     * @param ScriptWitnessInterface $witness
     * @return bool
     */
    public function equals(ScriptWitnessInterface $witness)
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
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function getBuffer()
    {
        return (new ScriptWitnessSerializer())->serialize($this);
    }
}
