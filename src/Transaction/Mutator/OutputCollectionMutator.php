<?php

namespace BitWasp\Bitcoin\Transaction\Mutator;

use BitWasp\Bitcoin\Collection\MutableCollection;
use BitWasp\Bitcoin\Collection\Transaction\TransactionOutputCollection;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class OutputCollectionMutator extends MutableCollection
{
    /**
     * @var \SplFixedArray
     */
    protected $set;

    /**
     * @param TransactionOutputInterface[] $outputs
     */
    public function __construct(array $outputs)
    {
        /** @var OutputMutator[] $set */
        $set = [];
        foreach ($outputs as $i => $output) {
            $set[$i] = new OutputMutator($output);
        }

        $this->set = \SplFixedArray::fromArray($set);
    }

    /**
     * @return OutputMutator
     */
    public function current()
    {
        return $this->set->current();
    }

    /**
     * @param int $offset
     * @return OutputMutator
     */
    public function offsetGet($offset)
    {
        if (!$this->set->offsetExists($offset)) {
            throw new \OutOfRangeException('Nothing found at this offset');
        }
        return $this->set->offsetGet($offset);
    }

    /**
     * @param int $index
     * @return OutputMutator
     */
    public function get($index)
    {
        return $this->offsetGet($index);
    }

    /**
     * @param int|string $i
     * @return OutputMutator
     */
    public function outputMutator($i)
    {
        if (!isset($this->set[$i])) {
            throw new \OutOfRangeException('Input does not exist');
        }

        /** @var OutputMutator $mutator */
        $mutator = $this->set[$i];
        return $mutator;
    }

    /**
     * @param int $i
     * @return \BitWasp\Bitcoin\Transaction\TransactionOutputInterface
     */
    public function getInput($i)
    {
        return $this->outputMutator($i)->done();
    }

    /**
     * @return TransactionOutputCollection
     */
    public function done()
    {
        $set = [];
        foreach ($this->set as $mutator) {
            $set[] = $mutator->done();
        }

        return new TransactionOutputCollection($set);
    }

    /**
     * @param int|string $start
     * @param int|string $length
     * @return $this
     */
    public function slice($start, $length)
    {
        $end = count($this->set);
        if ($start > $end || $length > $end) {
            throw new \RuntimeException('Invalid start or length');
        }

        $this->set = \SplFixedArray::fromArray(array_slice($this->set->toArray(), $start, $length));
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
     * @param TransactionOutputInterface $output
     * @return $this
     */
    public function add(TransactionOutputInterface $output)
    {
        $size = $this->set->getSize();
        $this->set->setSize($size + 1);

        $this->set[$size] = new OutputMutator($output);
        return $this;
    }

    /**
     * @param int $i
     * @param TransactionOutputInterface $output
     * @return $this
     */
    public function set($i, TransactionOutputInterface $output)
    {
        $this->set[$i] = new OutputMutator($output);
        return $this;
    }

    /**
     * @param int $i
     * @param TransactionOutputInterface $output
     * @return $this
     */
    public function update($i, TransactionOutputInterface $output)
    {
        $this->offsetGet($i);
        $this->offsetSet($i, $output);
        return $this;
    }

    /**
     * @param int $i
     * @param \Closure $closure
     * @return $this
     */
    public function applyTo($i, \Closure $closure)
    {
        $mutator = $this->offsetGet($i);
        $closure($mutator);
        return $this;
    }
}
