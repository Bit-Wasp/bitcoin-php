<?php

namespace BitWasp\Bitcoin\Script\Path;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Exceptions\ScriptRuntimeException;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Interpreter\Stack;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class AstInterpreter
{

    /**
     * @var \BitWasp\Bitcoin\Math\Math
     */
    private $math;

    /**
     * @var BufferInterface
     */
    private $vchFalse;

    /**
     * @var BufferInterface
     */
    private $vchTrue;

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
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $this->math = $ecAdapter->getMath();
        $this->vchFalse = new Buffer("", 0, $this->math);
        $this->vchTrue = new Buffer("\x01", 1, $this->math);
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
     * Cast the value to a boolean
     *
     * @param BufferInterface $value
     * @return bool
     */
    public function castToBool(BufferInterface $value)
    {
        $val = $value->getBinary();
        for ($i = 0, $size = strlen($val); $i < $size; $i++) {
            $chr = ord($val[$i]);
            if ($chr !== 0) {
                if (($i === ($size - 1)) && $chr === 0x80) {
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * @param $bool
     * @return Buffer|BufferInterface
     */
    public function boolToVector($bool)
    {
        return $bool ? $this->vchTrue : $this->vchFalse;
    }

    /**
     * @param ScriptInterface $script
     * @return AstNode
     */
    public function getAstForLogicalOps(ScriptInterface $script)
    {
        $root = new AstNode(null);
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
     * @param ScriptInterface $script
     * @param Stack $mainStack
     * @return array
     * @throws ScriptRuntimeException
     */
    public function evaluateUsingStack(ScriptInterface $script, Stack $mainStack)
    {
        $vfStack = new Stack();
        $parser = $script->getScriptParser();

        $segments = [];
        $trace = [];
        foreach ($parser as $i => $operation) {
            $opCode = $operation->getOp();
            $fExec = !$this->checkExec($vfStack, false);

            if ($operation->isPush() && $operation->getDataSize() > Interpreter::MAX_SCRIPT_ELEMENT_SIZE) {
                throw new \RuntimeException('Error - push size');
            }

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
                if (count($trace) > 0) {
                    $segments[] = $trace;
                }
                $segments[] = [$operation];
                $trace = [];
            } else if ($fExec) {
                $trace[] = $operation;
            }
        }

        if (count($vfStack) !== 0) {
            throw new \RuntimeException('Unbalanced conditional at script end');
        }

        if (count($mainStack) !== 0) {
            throw new \RuntimeException('Values remaining after script execution - invalid branch data');
        }

        if (count($trace) > 0) {
            $segments[] = $trace;
        }

        return $segments;
    }
}
