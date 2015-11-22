<?php

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Bitcoin\Script\Interpreter\Stack;

class State
{
    /**
     * @var Stack
     */
    private $mainStack;

    /**
     * @var Stack
     */
    private $vfStack;

    /**
     * @var Stack
     */
    private $altStack;

    public function __construct()
    {
        $this->mainStack = new Stack();
        $this->vfStack = new Stack();
        $this->altStack = new Stack();
    }

    /**
     * @return Stack
     */
    public function cloneMainStack()
    {
        return clone $this->mainStack;
    }

    /**
     * @param Stack $mainStack
     * @return $this
     */
    public function restoreMainStack(Stack $mainStack)
    {
        $this->mainStack = $mainStack;
        return $this;
    }

    /**
     * @return Stack
     */
    public function getMainStack()
    {
        return $this->mainStack;
    }

    /**
     * @return Stack
     */
    public function getVfStack()
    {
        return $this->vfStack;
    }

    /**
     * @return Stack
     */
    public function getAltStack()
    {
        return $this->altStack;
    }
}
