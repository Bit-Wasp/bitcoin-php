<?php

namespace BitWasp\Bitcoin\Collection;

interface CollectionInterface extends \Iterator, \ArrayAccess, \Countable
{
    /**
     * @return array
     */
    public function all();

    /**
     * @return bool
     */
    public function isNull();
}
