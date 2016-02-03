<?php

namespace BitWasp\Bitcoin\Collection\Transaction;

use BitWasp\Bitcoin\Collection\StaticCollection;
use BitWasp\Bitcoin\Transaction\TransactionInputWitnessInterface;

class TransactionWitnessCollection extends StaticCollection
{
    /**
     * Initialize a new collection with a list of Inputs.
     *
     * @param TransactionInputWitnessInterface[] $inputs
     */
    public function __construct(array $inputs = [])
    {
        $this->set = new \SplFixedArray(count($inputs));

        foreach ($inputs as $idx => $input) {
            if (!$input instanceof TransactionInputWitnessInterface) {
                throw new \InvalidArgumentException('Must provide TransactionInputWitnessInterface[] to TransactionWitnessCollection');
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
     * @return TransactionInputWitnessInterface
     */
    public function current()
    {
        return $this->set->current();
    }

    /**
     * @param int $offset
     * @return TransactionInputWitnessInterface
     */
    public function offsetGet($offset)
    {
        if (!$this->set->offsetExists($offset)) {
            throw new \OutOfRangeException('No offset found');
        }

        return $this->set->offsetGet($offset);
    }
}
