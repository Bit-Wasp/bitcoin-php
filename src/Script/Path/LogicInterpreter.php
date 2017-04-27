<?php

namespace BitWasp\Bitcoin\Script\Path;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\Interpreter\Number;
use BitWasp\Bitcoin\Script\Interpreter\Stack;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Exceptions\ScriptRuntimeException;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class LogicInterpreter
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
     * @param int $count
     * @return $this
     */
    private function checkOpcodeCount($count)
    {
        if ($count > 201) {
            throw new \RuntimeException('Error: Script op code count');
        }

        return $this;
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

    /**
     * @param ScriptInterface $script
     * @return ScriptInterface[]
     */
    public function astBranches(ScriptInterface $script)
    {
        $tree = $this->ast($script);

        $coords = $tree->stacks();
        $scripts = [];
        foreach ($coords as $mockStack) {
            $trace = [];
            if (!$this->evaluate($script, $mockStack, $trace)) {
                throw new \RuntimeException("Logic ");
            }

            $ops = [];
            foreach ($trace as $segment) {
                /** @var Operation[] $segment */
                foreach ($segment as $operation) {
                    $ops[] = $operation->encode();
                }
            }
            $scripts[] = ScriptFactory::sequence($ops);
        }

        return $scripts;
    }


    /**
     * @param ScriptInterface $script
     * @return ScriptInterface[]
     */
    public function getSegments(ScriptInterface $script)
    {
        $tree = $this->ast($script);
        $paths = $tree->getPaths($script);
        $segments = [];
        foreach ($paths as $path) {
            $segments[] = ScriptFactory::fromOperations($path);
        }

        return $segments;
    }

    /**
     * @param ScriptInterface $script
     * @param Stack $mainStack
     * @param array $trace
     * @return bool
     */
    public function evaluate(ScriptInterface $script, Stack $mainStack, array &$trace)
    {
        $opCount = 0;
        $altStack = new Stack();
        $vfStack = new Stack();
        $parser = $script->getScriptParser();

        if ($script->getBuffer()->getSize() > 10000) {
            return false;
        }

        $trace = [];

        try {
            $decoded = $parser->decode();
            $segment = [];

            foreach ($decoded as $operation) {
                $opCode = $operation->getOp();
                $fExec = !$this->checkExec($vfStack, false);

                // OP_RESERVED should not count towards opCount
                if ($opCode > Opcodes::OP_16 && ++$opCount) {
                    $this->checkOpcodeCount($opCount);
                }

                if (in_array($opCode, $this->disabledOps, true)) {
                    throw new \RuntimeException('Disabled Opcode');
                }

                if ($operation->isPush()) {
                    $segment[] = $operation;
                } elseif ((Opcodes::OP_IF <= $opCode && $opCode <= Opcodes::OP_ENDIF)) {
                    switch ($opCode) {
                        case Opcodes::OP_IF:
                        case Opcodes::OP_NOTIF:
                            // <expression> if [statements] [else [statements]] endif
                            $exprResult = false;
                            if ($fExec) {
                                if ($mainStack->isEmpty()) {
                                    throw new \RuntimeException('Unbalanced conditional');
                                }
                                $buffer = Number::buffer($mainStack->pop(), false)->getBuffer();
                                $trace[] = $segment;
                                $segment = [];

                                $exprResult = $this->castToBool($buffer);
                                if ($opCode === Opcodes::OP_NOTIF) {
                                    $exprResult = !$exprResult;
                                    echo "NOTIF\n";
                                    var_dump($exprResult);
                                } else {
                                    echo "IF\n";
                                    var_dump($exprResult);
                                }

                                // Invalidate segment if expr had false result
                                //if ($exprResult === $falseResult) {
                                //    echo "pop\n";
                                //    array_pop($trace);
                                //}

                            }
                            $vfStack->push($exprResult);
                            break;

                        case Opcodes::OP_ELSE:
                            if ($vfStack->isEmpty()) {
                                throw new \RuntimeException('Unbalanced conditional');
                            }
                            $trace[] = $segment;
                            $segment = [];
                            $end = $vfStack->pop();
                            if (!$end) {
                                array_pop($trace);
                            }
                            echo "ELSE\n";

                            $vfStack->push(!$end);
                            break;

                        case Opcodes::OP_ENDIF:
                            echo "ENDIF\n";

                            if ($vfStack->isEmpty()) {
                                throw new \RuntimeException('Unbalanced conditional');
                            }

                            $trace[] = $segment;
                            $segment = [];
                            $end = $vfStack->pop();
                            if (!$end) {
                                array_pop($trace);
                            }

                            break;
                    }
                } else {
                    switch($opCode) {
                        case Opcodes::OP_1NEGATE:
                        case Opcodes::OP_1:
                        case Opcodes::OP_2:
                        case Opcodes::OP_3:
                        case Opcodes::OP_4:
                        case Opcodes::OP_5:
                        case Opcodes::OP_6:
                        case Opcodes::OP_7:
                        case Opcodes::OP_8:
                        case Opcodes::OP_9:
                        case Opcodes::OP_10:
                        case Opcodes::OP_11:
                        case Opcodes::OP_12:
                        case Opcodes::OP_13:
                        case Opcodes::OP_14:
                        case Opcodes::OP_15:
                        case Opcodes::OP_16:
                        case Opcodes::OP_CHECKLOCKTIMEVERIFY:
                        case Opcodes::OP_CHECKSEQUENCEVERIFY:
                        case Opcodes::OP_NOP1:
                        case Opcodes::OP_NOP4:
                        case Opcodes::OP_NOP5:
                        case Opcodes::OP_NOP6:
                        case Opcodes::OP_NOP7:
                        case Opcodes::OP_NOP8:
                        case Opcodes::OP_NOP9:
                        case Opcodes::OP_NOP10:
                        case Opcodes::OP_NOP:
                        case Opcodes::OP_VERIFY:
                        case Opcodes::OP_TOALTSTACK:
                        case Opcodes::OP_FROMALTSTACK:
                        case Opcodes::OP_IFDUP:
                        case Opcodes::OP_DEPTH:
                        case Opcodes::OP_DROP:
                        case Opcodes::OP_DUP:
                        case Opcodes::OP_NIP:
                        case Opcodes::OP_OVER:
                        case Opcodes::OP_ROT:
                        case Opcodes::OP_SWAP:
                        case Opcodes::OP_TUCK:
                        case Opcodes::OP_PICK:
                        case Opcodes::OP_ROLL:
                        case Opcodes::OP_2DROP:
                        case Opcodes::OP_2DUP:
                        case Opcodes::OP_3DUP:
                        case Opcodes::OP_2OVER:
                        case Opcodes::OP_2ROT:
                        case Opcodes::OP_2SWAP:
                        case Opcodes::OP_SIZE:
                        case Opcodes::OP_EQUAL:
                        case Opcodes::OP_EQUALVERIFY:
                        case $opCode >= Opcodes::OP_1ADD && $opCode <= Opcodes::OP_0NOTEQUAL:
                        case $opCode >= Opcodes::OP_ADD && $opCode <= Opcodes::OP_MAX:
                        case Opcodes::OP_WITHIN:
                        case Opcodes::OP_RIPEMD160:
                        case Opcodes::OP_SHA1:
                        case Opcodes::OP_SHA256:
                        case Opcodes::OP_HASH160:
                        case Opcodes::OP_HASH256:
                        case Opcodes::OP_CODESEPARATOR:
                        case Opcodes::OP_CHECKSIG:
                        case Opcodes::OP_CHECKSIGVERIFY:
                        case Opcodes::OP_CHECKMULTISIG:
                        case Opcodes::OP_CHECKMULTISIGVERIFY:
                            $segment[] = $operation;
                            break;
                        default:
                            throw new \RuntimeException('Opcode not found: ' . $opCode);
                    }

                    if (count($mainStack) + count($altStack) > 1000) {
                        throw new \RuntimeException('Invalid stack size, exceeds 1000');
                    }

                    //print_r($segment);
                }
            }

            $trace[] = $segment;

            if (count($vfStack) !== 0) {
                throw new \RuntimeException('Unbalanced conditional at script end');
            }

            return true;
        } catch (ScriptRuntimeException $e) {
            // echo "\n Runtime: " . $e->getMessage() . "\n" . $e->getTraceAsString() . PHP_EOL;
            // Failure due to script tags, can access flag: $e->getFailureFlag()
            return false;
        } catch (\Exception $e) {
            // echo "\n General: " . $e->getMessage()  . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
            return false;
        }
    }
}
