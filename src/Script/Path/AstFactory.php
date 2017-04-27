<?php

namespace BitWasp\Bitcoin\Script\Path;

use BitWasp\Bitcoin\Script\Interpreter\Stack;
use BitWasp\Bitcoin\Script\ScriptInterface;

class AstFactory
{
    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @var AstInterpreter
     */
    private $interpreter;

    /**
     * AstFactory constructor.
     * @param ScriptInterface $script
     */
    public function __construct(ScriptInterface $script)
    {
        $this->script = $script;
        $this->interpreter = new AstInterpreter();
    }

    /**
     * @param array $path
     * @return ScriptBranch
     */
    private function getBranchForPath(array $path)
    {
        $stack = new Stack();
        foreach (array_reverse($path) as $setting) {
            $stack->push($setting);
        }

        $segments = $this->interpreter->evaluateUsingStack($this->script, $stack);

        return new ScriptBranch($this->script, $path, $segments);
    }

    /**
     * @return ScriptBranch[]
     */
    public function getScriptBranches()
    {
        $ast = $this->interpreter->getAstForLogicalOps($this->script);
        $paths = $ast->flags();
        $results = [];

        if (count($paths) > 1) {
            foreach ($paths as $path) {
                $results[] = $this->getBranchForPath($path);
            }
        } else {
            $results[] = $this->getBranchForPath([]);
        }

        return $results;
    }
}
