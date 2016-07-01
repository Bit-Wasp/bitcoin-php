<?php

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Buffertools\BufferInterface;

class Stack extends \SplDoublyLinkedList implements StackInterface
{
    public function __construct()
    {
        $this->setIteratorMode(\SplDoublyLinkedList::IT_MODE_FIFO | \SplDoublyLinkedList::IT_MODE_KEEP);
    }

    public function bottom()
    {
        return parent::offsetGet(count($this) - 1);
    }

    /**
     * @param BufferInterface $value
     * @throws \InvalidArgumentException
     */
    private function typeCheck($value)
    {
        if (!$value instanceof BufferInterface) {
            throw new \InvalidArgumentException('Value was not of type Buffer');
        }
    }

    /**
     * @see \ArrayAccess::offsetGet()
     * @param int $offset
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function offsetGet($offset)
    {
        $offset = count($this) + $offset;
        return parent::offsetGet($offset);
    }

    /**
     * @see \ArrayAccess::offsetSet()
     * @param int $offset
     * @param BufferInterface $value
     * @throws \InvalidArgumentException
     */
    public function offsetSet($offset, $value)
    {
        $this->typeCheck($value);
        $offset = count($this) + $offset;
        parent::offsetSet($offset, $value);
    }

    /**
     * @see \ArrayAccess::offsetExists()
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        $offset = count($this) + $offset;
        return parent::offsetExists($offset);
    }

    /**
     * @see \ArrayAccess::offsetUnset()
     * @param int $offset
     */
    public function offsetUnset($offset)
    {
        $offset = count($this) + $offset;
        parent::offsetUnset($offset);
    }

    /**
     * @param int $first
     * @param int $second
     */
    public function swap($first, $second)
    {
        $val1 = $this->offsetGet($first);
        $val2 = $this->offsetGet($second);
        $this->offsetSet($second, $val1);
        $this->offsetSet($first, $val2);
    }

    /**
     * @param int $index
     * @param BufferInterface $value
     */
    public function add($index, $value)
    {
        $this->typeCheck($value);

        if ($this->count() === 0 || $index === $this->count()) {
            $this->push($value);
        }

        $temp = [];
        for ($i = count($this); $i > $index; $i--) {
            echo $i.PHP_EOL;
            array_unshift($temp, $this->pop());
        }

        $this->push($value);
        foreach ($temp as $value) {
            $this->push($value);
        }
    }

    /**
     * @return int
     */
    public function end()
    {
        $count = count($this);
        if ($count === 0) {
            return 0;
        }

        return $count - 1;
    }

    /**
     * @param int $length
     * @return $this
     */
    public function resize($length)
    {
        if ($length > count($this)) {
            throw new \RuntimeException('Invalid start or length');
        }

        while (count($this) > $length) {
            $this->pop();
        }

        return $this;
    }
}
