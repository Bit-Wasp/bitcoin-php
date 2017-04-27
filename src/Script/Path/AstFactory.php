<?php

namespace BitWasp\Bitcoin\Script\Path;


use BitWasp\Bitcoin\Script\Interpreter\Stack;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;

class AstFactory
{
    public function __construct(ScriptInterface $script)
    {
        $this->script = $script;
        $this->ast = $this->ast($script);
        $this->interpreter = new AstInterpreter();
    }

    /**
     * @param ScriptInterface $script
     * @return MASTNode
     */
    public function ast(ScriptInterface $script)
    {
        $root = new MASTNode(null);
        $nextId = 1;
        $current = $root;
        $segments = [$root];

        foreach ($script->getScriptParser()->decode() as $op) {
            switch ($op->getOp()) {
                case Opcodes::OP_IF:
                    list ($node0, $node1) = $current->split();
                    $segments[$nextId++] = $node0;
                    $segments[$nextId++] = $node1;
                    $current = $node1;
                    break;
                case Opcodes::OP_NOTIF:
                    list ($node0, $node1) = $current->split();
                    $segments[$nextId++] = $node0;
                    $segments[$nextId++] = $node1;
                    $current = $node0;
                    break;
                case Opcodes::OP_ENDIF:
                    $current = $current->getParent();
                    break;
                case Opcodes::OP_ELSE:
                    if ($current->getValue() === false) {
                        throw new \RuntimeException("Unbalanced conditional");
                    }
                    $current = $current->getParent()->getChild(0);
                    break;
            }
        }

        return $root;
    }

    private function getBranchForPath(array $path)
    {
        $stack = new Stack();
        foreach(array_reverse($path) as $el) {
            $stack->push($el);
        }
        $segments = $this->interpreter->evaluateUsingStack($this->script, $stack, 0);
        $sequence = [];
        foreach ($segments as $segment) {
            echo " have " . count($segment) . "opcodes in segment\n";
            foreach ($segment as $operation) {
                $sequence[] = $operation;
            }
        }

        return new ScriptBranch($path, $segments, ScriptFactory::fromOperations($sequence));
    }

    public function getScriptBranches()
    {
        $paths = $this->ast->flags();
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