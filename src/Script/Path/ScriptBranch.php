<?php

namespace BitWasp\Bitcoin\Script\Path;


use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;

class ScriptBranch
{
    public function __construct(array $branch, array $segments, ScriptInterface $script)
    {
        $this->script = $script;
        $this->segments = $segments;
        $this->branch = $branch;
    }

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
}