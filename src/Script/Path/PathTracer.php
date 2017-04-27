<?php

namespace BitWasp\Bitcoin\Script\Path;


use BitWasp\Bitcoin\Script\Parser\Operation;

class PathTracer
{
    /**
     * Disable new operations when set
     *
     * @var bool
     */
    private $done = false;

    /**
     * Store segments of scripts
     *
     * @var OperationContainer[]
     */
    private $segments = [];

    /**
     * Temporary storage for current segment
     *
     * @var Operation[]
     */
    private $current = [];

    /**
     * Make a segment from whatever's in current
     */
    private function makeSegment()
    {
        $this->segments[] = new OperationContainer($this->current);
        $this->current = [];
    }

    /**
     * Add an operation to current segment
     * @param Operation $operation
     */
    private function addToCurrent(Operation $operation)
    {
        $this->current[] = $operation;
    }

    /**
     * @param Operation $operation
     */
    public function operation(Operation $operation)
    {
        if ($this->done) {
            throw new \RuntimeException("Cannot add operation to finished PathTracer");
        }

        if ($operation->isLogical()) {
            // Logical opcodes mean the end of a segment
            if (count($this->current) > 0) {
                $this->makeSegment();
            }

            $this->addToCurrent($operation);
            $this->makeSegment();

        } else {
            $this->addToCurrent($operation);
        }
    }

    /**
     * @return PathTrace
     */
    public function done()
    {
        if ($this->done) {
            return new PathTrace($this->segments);
        }

        if (count($this->current) > 0) {
            $this->makeSegment();
        }

        $this->done = true;

        return new PathTrace($this->segments);
    }
}