<?php

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Buffertools\Buffer;

interface StackInterface extends \ArrayAccess, \Iterator
{
    /**
     * @see \SplDoublyLinkedList::pop()
     * @return Buffer
     */
    public function pop();

    /**
     * @see \SplDoublyLinkedList::push()
     * @param Buffer $value
     * @return void
     */
    public function push($value);

    /**
     * @see \SplDoublyLinkedList::add()
     * @param int $offset
     * @param Buffer $value
     * @return void
     */
    public function add($offset, $value);

    /**
     * @see \SplDoublyLinkedList::bottom()
     * @return Buffer
     */
    public function bottom();

    /**
     * @see \SplDoublyLinkedList::top()
     * @return Buffer
     */
    public function top();

    /**
     * @see \SplDoublyLinkedList::isEmpty()
     * @return bool
     */
    public function isEmpty();

    /**
     * @see \SplDoublyLinkedList::prev()
     * @return void
     */
    public function prev();

    /**
     * @see \SplDoublyLinkedList::shift()
     * @return Buffer
     */
    public function shift();

    /**
     * @see \SplDoublyLinkedList::unshift()
     * @param Buffer $value
     * @return void
     */
    public function unshift($value);

    /**
     * @see \ArrayAccess::offsetGet()
     * @param int $offset
     * @return Buffer
     */
    public function offsetGet($offset);

    /**
     * @see \ArrayAccess::offsetExists()
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset);

    /**
     * @see \ArrayAccess::offsetUnset()
     * @param int $offset
     * @return void
     */
    public function offsetUnset($offset);

    /**
     * @see \ArrayAccess::offsetSet()
     * @param int $offset
     * @param Buffer $value
     * @return void
     */
    public function offsetSet($offset, $value);

    /**
     * Return the current element
     * @see \Iterator::current()
     * @return Buffer
     */
    public function current();

    /**
     * Move forward to next element
     * @see \Iterator::next()
     * @return void Any returned value is ignored.
     */
    public function next();

    /**
     * Return the key of the current element
     * @see \Iterator::key()
     * @return mixed scalar on success, or null on failure.
     */
    public function key();

    /**
     * Checks if current position is valid
     * @see \Iterator::valid()
     * @return boolean The return value will be casted to boolean and then evaluated.
     */
    public function valid();

    /**
     * Rewind to the first element
     * @see \Iterator::rewind()
     * @return void
     */
    public function rewind();

    /**
     * @see \Countable::count()
     * @return int
     */
    public function count();
}
