<?php

namespace BitWasp\Bitcoin\Script\Path;

use BitWasp\Bitcoin\Script\Interpreter\Stack;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptInterface;

class BranchInterpreter
{

    /**
     * @var array
     */
    private $disabledOps = [
        Opcodes::OP_CAT,    Opcodes::OP_SUBSTR, Opcodes::OP_LEFT,  Opcodes::OP_RIGHT,
        Opcodes::OP_INVERT, Opcodes::OP_AND,    Opcodes::OP_OR,    Opcodes::OP_XOR,
        Opcodes::OP_2MUL,   Opcodes::OP_2DIV,   Opcodes::OP_MUL,   Opcodes::OP_DIV,
        Opcodes::OP_MOD,    Opcodes::OP_LSHIFT, Opcodes::OP_RSHIFT
    ];

    /**
     * @param ScriptInterface $script
     * @return ScriptBranch[]
     */
    public function getScriptBranches(ScriptInterface $script)
    {
        $ast = $this->getAstForLogicalOps($script);
        $paths = $ast->flags();
        $results = [];

        if (count($paths) > 1) {
            foreach ($paths as $path) {
                $results[] = $this->getBranchForPath($script, $path);
            }
        } else {
            $results[] = $this->getBranchForPath($script, []);
        }

        return $results;
    }

    /**
     * Build tree of dependent logical ops
     * @param ScriptInterface $script
     * @return LogicOpNode
     */
    public function getAstForLogicalOps(ScriptInterface $script)
    {
        $root = new LogicOpNode(null);
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

    /**
     * Given a script and path, attempt to produce a ScriptBranch instance
     *
     * @param ScriptInterface $script
     * @param bool[] $path
     * @return ScriptBranch
     */
    public function getBranchForPath(ScriptInterface $script, array $path)
    {
        $stack = new Stack();
        foreach (array_reverse($path) as $setting) {
            $stack->push($setting);
        }

        $segments = $this->evaluateUsingStack($script, $stack);
        return new ScriptBranch($script, $path, $segments);
    }

    /**
     * @param Stack $vfStack
     * @param bool $value
     * @return bool
     */
    private function checkExec(Stack $vfStack, $value)
    {
        $ret = 0;
        foreach ($vfStack as $item) {
            if ($item === $value) {
                $ret++;
            }
        }

        return $ret;
    }

    /**
     * @param ScriptInterface $script
     * @param Stack $mainStack
     * @return PathTrace
     */
    public function evaluateUsingStack(ScriptInterface $script, Stack $mainStack)
    {
        $vfStack = new Stack();
        $parser = $script->getScriptParser();
        $tracer = new PathTracer();

        foreach ($parser as $i => $operation) {
            $opCode = $operation->getOp();
            $fExec = !$this->checkExec($vfStack, false);

            if (in_array($opCode, $this->disabledOps, true)) {
                throw new \RuntimeException('Disabled Opcode');
            }

            if (Opcodes::OP_IF <= $opCode && $opCode <= Opcodes::OP_ENDIF) {
                switch ($opCode) {
                    case Opcodes::OP_IF:
                    case Opcodes::OP_NOTIF:
                        // <expression> if [statements] [else [statements]] endif
                        $value = false;
                        if ($fExec) {
                            if ($mainStack->isEmpty()) {
                                throw new \RuntimeException('Unbalanced conditional');
                            }

                            $value = $mainStack->pop();
                            if ($opCode === Opcodes::OP_NOTIF) {
                                $value = !$value;
                            }
                        }
                        $vfStack->push($value);
                        break;

                    case Opcodes::OP_ELSE:
                        if ($vfStack->isEmpty()) {
                            throw new \RuntimeException('Unbalanced conditional');
                        }
                        $vfStack->push(!$vfStack->pop());
                        break;

                    case Opcodes::OP_ENDIF:
                        if ($vfStack->isEmpty()) {
                            throw new \RuntimeException('Unbalanced conditional');
                        }
                        $vfStack->pop();

                        break;
                }

                $tracer->operation($operation);

            } else if ($fExec) {
                // Fill up trace with executed opcodes
                $tracer->operation($operation);
            }
        }

        if (count($vfStack) !== 0) {
            throw new \RuntimeException('Unbalanced conditional at script end');
        }

        if (count($mainStack) !== 0) {
            throw new \RuntimeException('Values remaining after script execution - invalid branch data');
        }

        return $tracer->done();
    }
}
