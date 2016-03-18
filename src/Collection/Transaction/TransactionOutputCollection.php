<?php

namespace BitWasp\Bitcoin\Collection\Transaction;

use BitWasp\Bitcoin\Collection\StaticCollection;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class TransactionOutputCollection extends StaticCollection
{

    /**
     * Initialize a new collection with a list of outputs.
     *
     * @param TransactionOutputInterface[] $outputs
     */
    public function __construct(array $outputs = [])
    {
        $this->set = new \SplFixedArray(count($outputs));
        foreach ($outputs as $idx => $output) {
            if (!$output instanceof TransactionOutputInterface) {
                throw new \InvalidArgumentException('Must provide TransactionOutputInterface[] to TransactionOutputCollection');
            }
            $this->set->offsetSet($idx, $output);
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
     * @return TransactionOutputInterface[]
     */
    public function all()
    {
        return $this->set->toArray();
    }

    /**
     * @return TransactionOutputInterface
     */
    public function current()
    {
        return $this->set->current();
    }

    /**
     * @param int $offset
     * @return TransactionOutputInterface
     */
    public function offsetGet($offset)
    {
        if (!$this->set->offsetExists($offset)) {
            throw new \OutOfRangeException('No offset found');
        }

        return $this->set->offsetGet($offset);
    }
}
