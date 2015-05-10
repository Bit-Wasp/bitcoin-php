<?php

namespace BitWasp\Bitcoin\Script;


class ScriptInterpreterState
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
     * @param ScriptStack $mainStack
     * @param ScriptStack $vfStack
     * @param ScriptStack $altStack
     */
    public function __construct(
        ScriptStack $mainStack = null,
        ScriptStack $vfStack = null,
        ScriptStack $altStack = null
    ) {
        $this->mainStack = $mainStack ?: new ScriptStack();
        $this->vfStack = $vfStack ?: new ScriptStack();
        $this->altStack = $altStack ?: new ScriptStack();
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