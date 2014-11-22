<?php


namespace Bitcoin;

/**
 * Class ScriptStack
 * @package Bitcoin
 */
class ScriptStack
{

    /**
     * @var array
     */
    protected $stack = array();

    /**
     * @param $pos
     * @return int
     */
    private function getIndexFor($pos)
    {
        return count($this->stack) + $pos;
    }

    /**
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

        unset($this->stack[$index]);
        return $this;
    }

    /**
     * @param $pos
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
     * @param $value
     * @return $this
     */
    public function push($value)
    {
        array_push($this->stack, $value);
        return $this;
    }

    /**
     * @param $pos
     * @return mixed
     */
    public function top($pos)
    {
        $index = $this->getIndexFor($pos);
        return $this->stack[$index];
    }
} 