<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Exceptions\ScriptStackException;

class ScriptStack
{
    /**
     * @var array
     */
    private $stack = array();

    /**
     * Pop a value from the stack
     *
     * @return mixed
     * @throws ScriptStackException
     */
    public function pop()
    {
        if (count($this->stack) < 1) {
            throw new ScriptStackException('Attempted to pop from stack when empty');
        }

        return array_pop($this->stack);
    }

    /**
     * Push a value onto the stack
     *
     * @param $value
     * @return $this
     */
    public function push($value)
    {
        array_push($this->stack, $value);
        return $this;
    }

    /**
     * Get index of $pos relative to the top of the stack
     *
     * @param $pos
     * @return int
     */
    private function getIndexFor($pos)
    {
        $index = (count($this->stack) + $pos);
        return $index;
    }

    /**
     * Erase the item at $pos (relative to the top of the stack)
     *
     * @param $pos
     * @return $this
     * @throws ScriptStackException
     */
    public function erase($pos)
    {
        $index = $this->getIndexFor($pos);
        if (!isset($this->stack[$index])) {
            throw new ScriptStackException('No value in this location');
        }

        $this->stack = array_merge(array_slice($this->stack, 0, $index), array_slice($this->stack, $index + 1));

        return $this;
    }

    /**
     * Set $value to the $pos position in the stack (Relative to the top)
     *
     * @param integer $pos
     * @param $value
     * @return $this
     */
    public function set($pos, $value)
    {
        $index = $this->getIndexFor($pos);
        $this->stack[$index] = $value;
        return $this;
    }

    /**
     * Insert $value at a particular position
     *
     * @param integer $insertPosition
     * @param $value
     * @return $this
     */
    public function insert($insertPosition, $value)
    {
        $this->stack = array_merge(
            array_slice($this->stack, 0, $insertPosition, true),
            array($value),
            array_slice($this->stack, $insertPosition, count($this->stack) - $insertPosition, true)
        );

        return $this;
    }

    /**
     * Get the $pos value from the stack
     *
     * @param $pos
     * @return mixed
     */
    public function top($pos)
    {
        $index = $this->getIndexFor($pos);
        return $this->stack[$index];
    }

    /**
     * Dump the current stack
     *
     * @return array
     */
    public function dump()
    {
        return $this->stack;
    }

    /**
     * @return int
     */
    public function size()
    {
        return count($this->stack);
    }

    /**
     * @return int
     */
    public function end()
    {
        $count = $this->size();
        if ($count == 0) {
            return 0;
        }

        return $count - 1;
    }

    /**
     * @param integer $pos1
     * @param integer $pos2
     * @return $this
     */
    public function swap($pos1, $pos2)
    {
        $val1 = $this->top($pos1);
        $val2 = $this->top($pos2);
        $this->set($pos2, $val1);
        $this->set($pos1, $val2);
        return $this;
    }
}
