<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Mutator;

use BitWasp\Bitcoin\Transaction\TransactionInputInterface;

class InputCollectionMutator extends AbstractCollectionMutator
{
    /**
     * @param TransactionInputInterface[] $inputs
     */
    public function __construct(array $inputs)
    {
        /** @var InputMutator[] $set */
        $set = [];
        foreach ($inputs as $i => $input) {
            $set[$i] = new InputMutator($input);
        }

        $this->set = $set;
    }

    public function current(): InputMutator
    {
        return parent::current();
    }

    /**
     * @param int $offset
     * @return InputMutator
     */
    public function offsetGet($offset): InputMutator
    {
        return parent::offsetGet($offset);
    }

    /**
     * @return TransactionInputInterface[]
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
    public function slice(int $start, int $length): InputCollectionMutator
    {
        $end = $this->count();
        if ($start > $end || $length > $end) {
            throw new \RuntimeException('Invalid start or length');
        }

        $this->set = array_slice($this->set, $start, $length);
        return $this;
    }

    public function null(): InputCollectionMutator
    {
        $this->slice(0, 0);
        return $this;
    }

    public function add(TransactionInputInterface $input): InputCollectionMutator
    {
        $size = $this->count();
        $this->set[$size] = new InputMutator($input);
        return $this;
    }

    /**
     * @param int $i
     * @param TransactionInputInterface $input
     * @return $this
     */
    public function set(int $i, TransactionInputInterface $input): InputCollectionMutator
    {
        if ($i > count($this->set)) {
            throw new \InvalidArgumentException();
        }
        $this->set[$i] = new InputMutator($input);
        return $this;
    }
}
