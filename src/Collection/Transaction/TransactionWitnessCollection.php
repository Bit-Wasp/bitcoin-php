<?php

namespace BitWasp\Bitcoin\Collection\Transaction;

use BitWasp\Bitcoin\Collection\StaticCollection;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;

class TransactionWitnessCollection extends StaticCollection
{
    /**
     * Initialize a new collection with a list of Inputs.
     *
     * @param ScriptWitnessInterface[] $vScriptWitness
     */
    public function __construct(array $vScriptWitness = [])
    {
        $this->set = new \SplFixedArray(count($vScriptWitness));

        foreach ($vScriptWitness as $idx => $input) {
            if (!$input instanceof ScriptWitnessInterface) {
                throw new \InvalidArgumentException('Must provide ScriptWitnessInterface[] to TransactionWitnessCollection');
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
     * @return ScriptWitnessInterface[]
     */
    public function all()
    {
        return $this->set->toArray();
    }

    /**
     * @return ScriptWitnessInterface
     */
    public function current()
    {
        return $this->set->current();
    }

    /**
     * @param int $offset
     * @return ScriptWitnessInterface
     */
    public function offsetGet($offset)
    {
        if (!$this->set->offsetExists($offset)) {
            throw new \OutOfRangeException('No offset found');
        }

        return $this->set->offsetGet($offset);
    }
}
