<?php

namespace BitWasp\Bitcoin\Script\Path;

use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;

class ScriptBranch
{
    /**
     * @var ScriptInterface
     */
    private $fullScript;

    /**
     * @var array|\array[]
     */
    private $segments;

    /**
     * @var array|\bool[]
     */
    private $branch;

    /**
     * ScriptBranch constructor.
     * @param ScriptInterface $fullScript
     * @param array $branch
     * @param PathTrace $segments
     */
    public function __construct(ScriptInterface $fullScript, array $branch, PathTrace $segments)
    {
        $this->fullScript = $fullScript;
        $this->branch = $branch;
        $this->segments = $segments;
    }

    /**
     * @return ScriptInterface
     */
    public function getFullScript()
    {
        return $this->fullScript;
    }

    /**
     * @return array|\bool[]
     */
    public function getBranchDescriptor()
    {
        return $this->branch;
    }

    /**
     * @return array|\array[]|PathTrace
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * @return ScriptInterface
     */
    public function getNeuteredScript()
    {
        $sequence = [];
        foreach ($this->segments as $segment) {
            $sequence = array_merge($sequence, $segment->all());
        }

        return ScriptFactory::fromOperations($sequence);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $m = [];
        foreach ($this->segments as $segment) {
            $m[] = ScriptFactory::fromOperations($segment);
        }

        $path = [];
        foreach ($this->branch as $flag) {
            $path[] = $flag ? 'true' : 'false';
        }

        return [
            'branch' => implode(", ", $path),
            'segments' => $m,
        ];
    }

    /**
     * @return OperationContainer[]
     */
    public function getSignSteps()
    {
        $steps = [];
        foreach ($this->segments as $segment) {
            if (!$segment->isLoneLogicalOp()) {
                $steps[] = $segment;
            }
        }

        return $steps;
    }
}
