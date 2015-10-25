<?php

namespace BitWasp\Bitcoin\Collection;

interface CollectionInterface extends \Iterator, \ArrayAccess, \Countable
{
    /**
     * @param int $index
     * @return mixed
     */
    public function get($index);

    /**
     * @return array
     */
    public function all();
}
