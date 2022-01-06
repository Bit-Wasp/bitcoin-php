<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Mutator;

use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class OutputCollectionMutator extends AbstractCollectionMutator
{
    /**
     * @param TransactionOutputInterface[] $outputs
     */
    public function __construct(array $outputs)
    {
        /** @var OutputMutator[] $set */
        $set = [];
        foreach ($outputs as $i => $output) {
            /** @var int $i */
            $set[$i] = new OutputMutator($output);
        }

        $this->set = $set;
    }

    public function current(): OutputMutator
    {
        return parent::current();
    }

    /**
     * @param int $offset
     * @return OutputMutator
     */
    public function offsetGet($offset): OutputMutator
    {
        return parent::offsetGet($offset);
    }

    /**
     * @return TransactionOutputInterface[]
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
     * Return an array containing values beginning at index $start and ending
     * with index $start + $length. An exception is thrown if start or $length
     * is out of bounds
     */
    public function slice(int $start, int $length): OutputCollectionMutator
    {
        $end = count($this->set);
        if ($start > $end || $length > $end) {
            throw new \RuntimeException('Invalid start or length');
        }

        $this->set = array_slice($this->set, $start, $length);
        return $this;
    }

    public function null(): OutputCollectionMutator
    {
        $this->slice(0, 0);
        return $this;
    }

    public function add(TransactionOutputInterface $output): OutputCollectionMutator
    {
        $size = $this->count();
        $this->set[$size] = new OutputMutator($output);
        return $this;
    }

    /**
     * @param int $i
     * @param TransactionOutputInterface $output
     * @return $this
     */
    public function set(int $i, TransactionOutputInterface $output): OutputCollectionMutator
    {
        if ($i > count($this->set)) {
            throw new \InvalidArgumentException();
        }
        $this->set[$i] = new OutputMutator($output);
        return $this;
    }
}
