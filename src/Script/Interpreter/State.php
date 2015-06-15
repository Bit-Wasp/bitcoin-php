<?php

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Bitcoin\Script\ScriptStack;

class State
{
    /**
     * @var ScriptStack
     */
    private $mainStack;

    /**
     * @var ScriptStack
     */
    private $vfStack;

    /**
     * @var ScriptStack
     */
    private $altStack;

    /**
     *
     */
    public function __construct()
    {
        $this->mainStack = new ScriptStack();
        $this->vfStack = new ScriptStack();
        $this->altStack = new ScriptStack();
    }

    /**
     * @return ScriptStack
     */
    public function cloneMainStack()
    {
        return clone $this->mainStack;
    }

    /**
     * @param ScriptStack $mainStack
     * @return $this
     */
    public function restoreMainStack(ScriptStack $mainStack)
    {
        $this->mainStack = $mainStack;
        return $this;
    }

    /**
     * @return ScriptStack
     */
    public function getMainStack()
    {
        return $this->mainStack;
    }

    /**
     * @return ScriptStack
     */
    public function getVfStack()
    {
        return $this->vfStack;
    }

    /**
     * @return ScriptStack
     */
    public function getAltStack()
    {
        return $this->altStack;
    }
}
