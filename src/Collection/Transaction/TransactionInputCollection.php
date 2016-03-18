<?php

namespace BitWasp\Bitcoin\Collection\Transaction;

use BitWasp\Bitcoin\Collection\StaticCollection;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;

class TransactionInputCollection extends StaticCollection
{
    /**
     * Initialize a new collection with a list of Inputs.
     *
     * @param TransactionInputInterface[] $inputs
     */
    public function __construct(array $inputs = [])
    {
        $this->set = new \SplFixedArray(count($inputs));
        foreach ($inputs as $idx => $input) {
            if (!$input instanceof TransactionInputInterface) {
                throw new \InvalidArgumentException('Must provide TransactionInputInterface[] to TransactionInputCollection');
            }
            $this->set->offsetSet($idx, $input);
        }
    }

    public function __clone()
    {
        $inputs = $this->set;
        $this->set = new \SplFixedArray(count($inputs));

        foreach ($inputs as $idx => $input) {
            $this->set->offsetSet($idx, $input);
        }
    }

    /**
     * @return TransactionInputInterface[]
     */
    public function all()
    {
        return $this->set->toArray();
    }

    /**
     * @return TransactionInputInterface
     */
    public function current()
    {
        return $this->set->current();
    }

    /**
     * @param int $offset
     * @return TransactionInputInterface
     */
    public function offsetGet($offset)
    {
        if (!$this->set->offsetExists($offset)) {
            throw new \OutOfRangeException('No offset found');
        }

        return $this->set->offsetGet($offset);
    }
}
