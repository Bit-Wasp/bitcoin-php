<?php

namespace BitWasp\Bitcoin\Collection\Generic;

use BitWasp\Bitcoin\Collection\StaticCollection;

class Set extends StaticCollection
{

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->set = \SplFixedArray::fromArray($values, false);
    }
}
