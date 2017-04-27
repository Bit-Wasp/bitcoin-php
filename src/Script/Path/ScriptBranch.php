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
     * @param bool[] $branch
     * @param array[] $segments
     */
    public function __construct(ScriptInterface $fullScript, array $branch, array $segments)
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
     * @return array[]
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
            foreach ($segment as $operation) {
                $sequence[] = $operation;
            }
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
     * @return array
     */
    public function getSignSteps()
    {
        $steps = [];
        foreach ($this->segments as $segment) {
            if (count($segment) === 1 && $segment[0]->isLogical()) {
            } else {
                $steps[] = $segment;
            }
        }
        return $steps;
    }
}
