<?php

namespace BitWasp\Bitcoin\Script\Path;

use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;

class ScriptBranch
{
    /**
     * ScriptBranch constructor.
     * @param ScriptInterface $fullScript
     * @param bool[] $branch
     * @param array[] $segments
     * @param ScriptInterface $script
     */
    public function __construct(ScriptInterface $fullScript, array $branch, array $segments, ScriptInterface $script)
    {
        $this->script = $script;
        $this->segments = $segments;
        $this->branch = $branch;
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