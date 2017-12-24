<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Mutator;

use BitWasp\Bitcoin\Script\ScriptWitnessInterface;

class WitnessCollectionMutator extends AbstractCollectionMutator
{

    /**
     * @param ScriptWitnessInterface[] $inputs
     */
    public function __construct(array $inputs)
    {
        /** @var InputMutator[] $set */
        $set = [];
        foreach ($inputs as $i => $input) {
            $set[$i] = new InputMutator($input);
        }

        $this->set = \SplFixedArray::fromArray($set, false);
    }

    /**
     * @return InputMutator
     */
    public function current()
    {
        return $this->set->current();
    }

    /**
     * @param int $offset
     * @return InputMutator
     */
    public function offsetGet($offset): InputMutator
    {
        if (!$this->set->offsetExists($offset)) {
            throw new \OutOfRangeException('Input does not exist');
        }

        return $this->set->offsetGet($offset);
    }

    /**
     * @return ScriptWitnessInterface[]
     */
    public function done(): array
    {
        $set = [];
        foreach ($this->set as $mutator) {
            $set[] = $mutator->done();
        }

        return $set;
    }

    /**
     * @param int $start
     * @param int $length
     * @return $this
     */
    public function slice(int $start, int $length)
    {
        $end = $this->set->getSize();
        if ($start > $end || $length > $end) {
            throw new \RuntimeException('Invalid start or length');
        }

        $this->set = \SplFixedArray::fromArray(array_slice($this->set->toArray(), $start, $length), false);
        return $this;
    }

    /**
     * @return $this
     */
    public function null()
    {
        $this->slice(0, 0);
        return $this;
    }

    /**
     * @param ScriptWitnessInterface $witness
     * @return $this
     */
    public function add(ScriptWitnessInterface $witness)
    {
        $size = $this->set->getSize();
        $this->set->setSize($size + 1);

        $this->set[$size] = new InputMutator($witness);
        return $this;
    }

    /**
     * @param int $i
     * @param ScriptWitnessInterface $input
     * @return $this
     */
    public function set(int $i, ScriptWitnessInterface $input)
    {
        $this->set[$i] = new InputMutator($input);
        return $this;
    }
}
