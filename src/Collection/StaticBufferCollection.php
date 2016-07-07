<?php

namespace BitWasp\Bitcoin\Collection;

use BitWasp\Buffertools\BufferInterface;

class StaticBufferCollection extends StaticCollection
{
    /**
     * @var BufferInterface[]
     */
    protected $set = [];

    /**
     * @var int
     */
    protected $position = 0;

    /**
     * StaticBufferCollection constructor.
     * @param BufferInterface[] $sigValues
     */
    public function __construct(array $sigValues)
    {
        array_map(function (BufferInterface $data) {
            $this->set[] = $data;
        }, $sigValues);
    }

    /**
     * @return BufferInterface
     */
    public function bottom()
    {
        return parent::bottom();
    }

    /**
     * @return BufferInterface
     */
    public function top()
    {
        return parent::top();
    }

    /**
     * @return BufferInterface
     */
    public function current()
    {
        return $this->set[$this->position];
    }

    /**
     * @param int $offset
     * @return BufferInterface
     */
    public function offsetGet($offset)
    {
        if (!array_key_exists($offset, $this->set)) {
            throw new \OutOfRangeException('No offset found');
        }

        return $this->set[$offset];
    }
}
