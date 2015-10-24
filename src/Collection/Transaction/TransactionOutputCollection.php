<?php

namespace BitWasp\Bitcoin\Collection\Transaction;

use BitWasp\Bitcoin\Collection\StaticCollection;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class TransactionOutputCollection extends StaticCollection
{
    /**
     * @var \SplFixedArray
     */
    protected $set;

    /**
     * Initialize a new collection with a list of outputs.
     *
     * @param TransactionOutputInterface[] $inputs
     */
    public function __construct(array $inputs = [])
    {
        foreach ($inputs as $input) {
            if (!$input instanceof TransactionOutputInterface) {
                throw new \InvalidArgumentException('Must provide TransactionOutputInterface[] to TransactionOutputCollection');
            }
        }

        $this->set = \SplFixedArray::fromArray($inputs);
    }

    public function __clone()
    {
        $this->set = \SplFixedArray::fromArray(array_map(
            function (TransactionOutputInterface $txIn) {
                return clone $txIn;
            },
            $this->set->toArray()
        ));
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

    /**
     * @param int $offset
     * @return TransactionOutputInterface
     */
    public function get($offset)
    {
        return $this->offsetGet($offset);
    }
}
