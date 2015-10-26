<?php

namespace BitWasp\Bitcoin\Collection\Generic;

use BitWasp\Bitcoin\Collection\StaticCollection;

class Set extends StaticCollection
{
    /**
     * @var \SplFixedArray
     */
    protected $set;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->set = \SplFixedArray::fromArray($values);
    }
}
