<?php

namespace BitWasp\Bitcoin\Collection;

interface CollectionInterface extends \Iterator, \ArrayAccess, \Countable
{
    /**
     * @return array
     */
    public function all();

    /**
     * @return mixed
     */
    public function bottom();

    /**
     * @return mixed
     */
    public function top();
    
    /**
     * @param int $start
     * @param int $length
     * @return self
     */
    public function slice($start, $length);

    /**
     * @return bool
     */
    public function isNull();
}
