<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Collection;

/**
 * @deprecated v2.0.0
 */
interface CollectionInterface extends \Iterator, \ArrayAccess, \Countable
{
    /**
     * @return array
     */
    public function all(): array;

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
    public function slice(int $start, int $length);

    /**
     * @return bool
     */
    public function isNull(): bool;
}
