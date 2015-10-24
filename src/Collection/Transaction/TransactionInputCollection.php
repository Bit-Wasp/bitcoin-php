<?php

namespace BitWasp\Bitcoin\Collection\Transaction;

use BitWasp\Bitcoin\Collection\StaticCollection;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;

class TransactionInputCollection extends StaticCollection
{
    /**
     * @var \SplFixedArray
     */
    protected $set;

    /**
     * Initialize a new collection with a list of inputs.
     *
     * @param TransactionInputInterface[] $inputs
     */
    public function __construct(array $inputs = [])
    {
        foreach ($inputs as $input) {
            if (!$input instanceof TransactionInputInterface) {
                throw new \InvalidArgumentException('Must provide TransactionInputInterface[] to TransactionInputCollection');
            }
        }

        $this->set = \SplFixedArray::fromArray($inputs);
    }

    public function __clone()
    {
        $this->set = \SplFixedArray::fromArray(array_map(
            function (TransactionInputInterface $txIn) {
                return clone $txIn;
            },
            $this->set->toArray()
        ));
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

    /**
     * @param int $offset
     * @return TransactionInputInterface
     */
    public function get($offset)
    {
        return $this->offsetGet($offset);
    }
}
