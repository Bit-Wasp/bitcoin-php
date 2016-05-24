<?php

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Exceptions\SignatureNotCanonical;
use BitWasp\Bitcoin\Exceptions\ScriptRuntimeException;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class Interpreter implements InterpreterInterface
{

    /**
     * @var int
     */
    private $opCount;

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
    public function __construct(EcAdapterInterface $ecAdapter)
    {
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
            if ($chr != 0) {
                if ($i == ($size - 1) && $chr == 0x80) {
                    return false;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @param BufferInterface $signature
     * @return bool
     */
    public function isValidSignatureEncoding(BufferInterface $signature)
    {
        try {
            TransactionSignature::isDERSignature($signature);
            return true;
        } catch (SignatureNotCanonical $e) {
            /* In any case, we will return false outside this block */
        }

        return false;
    }

    /**
     * @param int $opCode
     * @param BufferInterface $pushData
     * @return bool
     * @throws \Exception
     */
    public function checkMinimalPush($opCode, BufferInterface $pushData)
    {
        $pushSize = $pushData->getSize();
        $binary = $pushData->getBinary();

        if ($pushSize === 0) {
            return $opCode === Opcodes::OP_0;
        } elseif ($pushSize === 1) {
            $first = ord($binary[0]);
            if ($first >= 1 && $first <= 16) {
                return $opCode === (Opcodes::OP_1 + ($first - 1));
            } elseif ($first === 0x81) {
                return $opCode === Opcodes::OP_1NEGATE;
            }
        } elseif ($pushSize <= 75) {
            return $opCode === $pushSize;
        } elseif ($pushSize <= 255) {
            return $opCode === Opcodes::OP_PUSHDATA1;
        } elseif ($pushSize <= 65535) {
            return $opCode === Opcodes::OP_PUSHDATA2;
        }

        return true;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    private function checkOpcodeCount()
    {
        if ($this->math->cmp($this->opCount, 201) > 0) {
            throw new \RuntimeException('Error: Script op code count');
        }

        return $this;
    }

    /**
     * @param WitnessProgram $witnessProgram
     * @param ScriptWitnessInterface $scriptWitness
     * @param int $flags
     * @param Checker $checker
     * @return bool
     */
    private function verifyWitnessProgram(WitnessProgram $witnessProgram, ScriptWitnessInterface $scriptWitness, $flags, Checker $checker)
    {
        $witnessCount = count($scriptWitness);

        if ($witnessProgram->getVersion() == 0) {
            $buffer = $witnessProgram->getProgram();
            if ($buffer->getSize() === 32) {
                // Version 0 segregated witness program: SHA256(Script) in program, Script + inputs in witness
                if ($witnessCount === 0) {
                    // Must contain script at least
                    return false;
                }

                $scriptPubKey = new Script($scriptWitness[$witnessCount - 1]);
                $stackValues = $scriptWitness->slice(0, -1);
                $hashScriptPubKey = Hash::sha256($scriptPubKey->getBuffer());

                if ($hashScriptPubKey == $buffer) {
                    return false;
                }
            } elseif ($buffer->getSize() === 20) {
                // Version 0 special case for pay-to-pubkeyhash
                if ($witnessCount !== 2) {
                    // 2 items in witness - <signature> <pubkey>
                    return false;
                }

                $scriptPubKey = ScriptFactory::create()->sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $buffer, Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG])->getScript();
                $stackValues = $scriptWitness;
            } else {
                return false;
            }
        } elseif ($flags & self::VERIFY_DISCOURAGE_UPGRADABLE_WITNESS_PROGRAM) {
            return false;
        } else {
            return false;
        }

        $mainStack = new Stack();
        foreach ($stackValues as $value) {
            $mainStack->push($value);
        }

        if (!$this->evaluate($scriptPubKey, $mainStack, 1, $flags, $checker)) {
            return false;
        }

        if ($mainStack->count() !== 1) {
            return false;
        }

        if (!$this->castToBool($mainStack->bottom())) {
            return false;
        }

        return true;
    }

    /**
     * @param ScriptInterface $scriptSig
     * @param ScriptInterface $scriptPubKey
     * @param int $flags
     * @param Checker $checker
     * @param ScriptWitnessInterface|null $witness
     * @return bool
     */
    public function verify(ScriptInterface $scriptSig, ScriptInterface $scriptPubKey, $flags, Checker $checker, ScriptWitnessInterface $witness = null)
    {
        static $emptyWitness = null;
        if ($emptyWitness === null) {
            $emptyWitness = new ScriptWitness([]);
        }

        $witness = is_null($witness) ? $emptyWitness : $witness;

        if (($flags & self::VERIFY_SIGPUSHONLY) != 0 && !$scriptSig->isPushOnly()) {
            return false;
        }

        $stack = new Stack();
        if (!$this->evaluate($scriptSig, $stack, 0, $flags, $checker)) {
            return false;
        }

        $backup = [];
        if ($flags & self::VERIFY_P2SH) {
            foreach ($stack as $s) {
                $backup[] = $s;
            }
        }

        if (!$this->evaluate($scriptPubKey, $stack, 0, $flags, $checker)) {
            return false;
        }

        if ($stack->isEmpty()) {
            return false;
        }

        if (false === $this->castToBool($stack[-1])) {
            return false;
        }

        $program = null;
        if ($flags & self::VERIFY_WITNESS) {
            if ($scriptPubKey->isWitness($program)) {
                /** @var WitnessProgram $program */
                if ($scriptSig->getBuffer()->getSize() !== 0) {
                    return false;
                }

                if (!$this->verifyWitnessProgram($program, $witness, $flags, $checker)) {
                    return false;
                }

                $stack->resize(1);
            }
        }

        if ($flags & self::VERIFY_P2SH && (new OutputClassifier())->isPayToScriptHash($scriptPubKey)) {
            if (!$scriptSig->isPushOnly()) {
                return false;
            }

            $stack = new Stack();
            foreach ($backup as $i) {
                $stack->push($i);
            }

            // Restore mainStack to how it was after evaluating scriptSig
            if ($stack->isEmpty()) {
                return false;
            }

            // Load redeemscript as the scriptPubKey
            $scriptPubKey = new Script($stack->bottom());
            $stack->pop();

            if (!$this->evaluate($scriptPubKey, $stack, 0, $flags, $checker)) {
                return false;
            }

            if ($stack->isEmpty()) {
                return false;
            }

            if (!$this->castToBool($stack->bottom())) {
                return false;
            }

            if ($flags & self::VERIFY_WITNESS) {
                if ($scriptPubKey->isWitness($program)) {
                    /** @var WitnessProgram $program */
                    if ($scriptSig != (ScriptFactory::create()->push($scriptPubKey->getBuffer())->getScript())) {
                        return false; // SCRIPT_ERR_WITNESS_MALLEATED_P2SH
                    }

                    if (!$this->verifyWitnessProgram($program, $witness, $flags, $checker)) {
                        return false;
                    }

                    $stack->resize(1);
                }
            }
        }

        if ($flags & self::VERIFY_CLEAN_STACK != 0) {
            if (!($flags & self::VERIFY_P2SH != 0 && $flags & self::VERIFY_WITNESS != 0)) {
                return false; // implied flags required
            }

            if (count($stack) != 1) {
                return false; // Cleanstack
            }
        }

        if ($flags & self::VERIFY_WITNESS) {
            if (!$flags & self::VERIFY_P2SH) {
                return false; //
            }

            if ($program === null && !$witness->isNull()) {
                return false; // SCRIPT_ERR_WITNESS_UNEXPECTED
            }
        }

        return true;
    }

    /**
     * @param Stack $vfStack
     * @return bool
     */
    private function checkExec(Stack $vfStack)
    {
        $c = 0;
        $len = $vfStack->end();
        for ($i = 0; $i < $len; $i++) {
            if ($vfStack[0 - $len - $i] === true) {
                $c++;
            }
        }

        return !(bool)$c;
    }

    /**
     * @param ScriptInterface $script
     * @param Stack $mainStack
     * @param int $sigVersion
     * @param int $flags
     * @param Checker $checker
     * @return bool
     */
    public function evaluate(ScriptInterface $script, Stack $mainStack, $sigVersion, $flags, Checker $checker)
    {
        $math = $this->math;
        $hashStartPos = 0;
        $this->opCount = 0;
        $altStack = new Stack();
        $vfStack = new Stack();
        $minimal = ($flags & self::VERIFY_MINIMALDATA) != 0;
        $parser = $script->getScriptParser();

        if ($script->getBuffer()->getSize() > 10000) {
            return false;
        }

        try {
            foreach ($parser as $operation) {
                $opCode = $operation->getOp();
                $pushData = $operation->getData();
                $fExec = $this->checkExec($vfStack);

                // If pushdata was written to
                if ($operation->isPush() && $operation->getDataSize() > InterpreterInterface::MAX_SCRIPT_ELEMENT_SIZE) {
                    throw new \RuntimeException('Error - push size');
                }

                // OP_RESERVED should not count towards opCount
                if ($opCode > Opcodes::OP_16 && ++$this->opCount) {
                    $this->checkOpcodeCount();
                }

                if (in_array($opCode, $this->disabledOps, true)) {
                    throw new \RuntimeException('Disabled Opcode');
                }

                if ($fExec && $operation->isPush()) {
                    // In range of a pushdata opcode
                    if ($minimal && !$this->checkMinimalPush($opCode, $pushData)) {
                        throw new ScriptRuntimeException(self::VERIFY_MINIMALDATA, 'Minimal pushdata required');
                    }

                    $mainStack->push($pushData);
                    // echo " - [pushed '" . $pushData->getHex() . "']\n";
                } elseif ($fExec || ($opCode !== Opcodes::OP_IF && $opCode !== Opcodes::OP_ENDIF)) {
                    // echo "OPCODE - " . $script->getOpcodes()->getOp($opCode) . "\n";
                    switch ($opCode) {
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
                            $num = \BitWasp\Bitcoin\Script\decodeOpN($opCode);
                            $mainStack->push(Number::int($num)->getBuffer());
                            break;

                        case Opcodes::OP_CHECKLOCKTIMEVERIFY:
                            if (!$flags & self::VERIFY_CHECKLOCKTIMEVERIFY) {
                                if ($flags & self::VERIFY_DISCOURAGE_UPGRADABLE_NOPS) {
                                    throw new ScriptRuntimeException(self::VERIFY_DISCOURAGE_UPGRADABLE_NOPS, 'Upgradable NOP found - this is discouraged');
                                }
                                break;
                            }

                            if ($mainStack->isEmpty()) {
                                throw new \RuntimeException('Invalid stack operation - CLTV');
                            }

                            $lockTime = Number::buffer($mainStack[-1], $minimal, 5, $math);
                            if (!$checker->checkLockTime($lockTime)) {
                                throw new ScriptRuntimeException(self::VERIFY_CHECKLOCKTIMEVERIFY, 'Unsatisfied locktime');
                            }

                            break;

                        case Opcodes::OP_CHECKSEQUENCEVERIFY:
                            if (!$flags & self::VERIFY_CHECKSEQUENCEVERIFY) {
                                if ($flags & self::VERIFY_DISCOURAGE_UPGRADABLE_NOPS) {
                                    throw new ScriptRuntimeException(self::VERIFY_DISCOURAGE_UPGRADABLE_NOPS, 'Upgradable NOP found - this is discouraged');
                                }
                                break;
                            }

                            if ($mainStack->isEmpty()) {
                                throw new \RuntimeException('Invalid stack operation - CSV');
                            }

                            $sequence = Number::buffer($mainStack[-1], $minimal, 5, $math);
                            $nSequence = $sequence->getInt();
                            if ($math->cmp($nSequence, 0) < 0) {
                                throw new ScriptRuntimeException(self::VERIFY_CHECKSEQUENCEVERIFY, 'Negative locktime');
                            }

                            if ($math->cmp($math->bitwiseAnd($nSequence, TransactionInputInterface::SEQUENCE_LOCKTIME_DISABLE_FLAG), '0') !== 0) {
                                break;
                            }

                            if (!$checker->checkSequence($sequence)) {
                                throw new ScriptRuntimeException(self::VERIFY_CHECKSEQUENCEVERIFY, 'Unsatisfied locktime');
                            }
                            break;

                        case Opcodes::OP_NOP1:
                        case Opcodes::OP_NOP4:
                        case Opcodes::OP_NOP5:
                        case Opcodes::OP_NOP6:
                        case Opcodes::OP_NOP7:
                        case Opcodes::OP_NOP8:
                        case Opcodes::OP_NOP9:
                        case Opcodes::OP_NOP10:
                            if ($flags & self::VERIFY_DISCOURAGE_UPGRADABLE_NOPS) {
                                throw new ScriptRuntimeException(self::VERIFY_DISCOURAGE_UPGRADABLE_NOPS, 'Upgradable NOP found - this is discouraged');
                            }
                            break;

                        case Opcodes::OP_NOP:
                            break;

                        case Opcodes::OP_IF:
                        case Opcodes::OP_NOTIF:
                            // <expression> if [statements] [else [statements]] endif
                            $value = false;
                            if ($fExec) {
                                if ($mainStack->isEmpty()) {
                                    throw new \RuntimeException('Unbalanced conditional');
                                }

                                $buffer = Number::buffer($mainStack->pop(), $minimal)->getBuffer();
                                $value = $this->castToBool($buffer);

                                if ($opCode === Opcodes::OP_NOTIF) {
                                    $value = !$value;
                                }
                            }
                            $vfStack->push($value ? $this->vchTrue : $this->vchFalse);
                            break;

                        case Opcodes::OP_ELSE:
                            if ($vfStack->isEmpty()) {
                                throw new \RuntimeException('Unbalanced conditional');
                            }
                            $vfStack->push(!$vfStack->end() ? $this->vchTrue : $this->vchFalse);
                            break;

                        case Opcodes::OP_ENDIF:
                            if ($vfStack->isEmpty()) {
                                throw new \RuntimeException('Unbalanced conditional');
                            }
                            break;

                        case Opcodes::OP_VERIFY:
                            if ($mainStack->isEmpty()) {
                                throw new \RuntimeException('Invalid stack operation');
                            }
                            $value = $this->castToBool($mainStack[-1]);
                            if (!$value) {
                                throw new \RuntimeException('Error: verify');
                            }
                            $mainStack->pop();
                            break;

                        case Opcodes::OP_TOALTSTACK:
                            if ($mainStack->isEmpty()) {
                                throw new \RuntimeException('Invalid stack operation OP_TOALTSTACK');
                            }
                            $altStack->push($mainStack->pop());
                            break;

                        case Opcodes::OP_FROMALTSTACK:
                            if ($altStack->isEmpty()) {
                                throw new \RuntimeException('Invalid alt-stack operation OP_FROMALTSTACK');
                            }
                            $mainStack->push($altStack->pop());
                            break;

                        case Opcodes::OP_IFDUP:
                            // If top value not zero, duplicate it.
                            if ($mainStack->isEmpty()) {
                                throw new \RuntimeException('Invalid stack operation OP_IFDUP');
                            }
                            $vch = $mainStack[-1];
                            if ($this->castToBool($vch)) {
                                $mainStack->push($vch);
                            }
                            break;

                        case Opcodes::OP_DEPTH:
                            $num = count($mainStack);
                            $depth = Number::int($num)->getBuffer();
                            $mainStack->push($depth);
                            break;

                        case Opcodes::OP_DROP:
                            if ($mainStack->isEmpty()) {
                                throw new \RuntimeException('Invalid stack operation OP_DROP');
                            }
                            $mainStack->pop();
                            break;

                        case Opcodes::OP_DUP:
                            if ($mainStack->isEmpty()) {
                                throw new \RuntimeException('Invalid stack operation OP_DUP');
                            }
                            $vch = $mainStack[-1];
                            $mainStack->push($vch);
                            break;

                        case Opcodes::OP_NIP:
                            if (count($mainStack) < 2) {
                                throw new \RuntimeException('Invalid stack operation OP_NIP');
                            }
                            unset($mainStack[-2]);
                            break;

                        case Opcodes::OP_OVER:
                            if (count($mainStack) < 2) {
                                throw new \RuntimeException('Invalid stack operation OP_OVER');
                            }
                            $vch = $mainStack[-2];
                            $mainStack->push($vch);
                            break;

                        case Opcodes::OP_ROT:
                            if (count($mainStack) < 3) {
                                throw new \RuntimeException('Invalid stack operation OP_ROT');
                            }
                            $mainStack->swap(-3, -2);
                            $mainStack->swap(-2, -1);
                            break;

                        case Opcodes::OP_SWAP:
                            if (count($mainStack) < 2) {
                                throw new \RuntimeException('Invalid stack operation OP_SWAP');
                            }
                            $mainStack->swap(-2, -1);
                            break;

                        case Opcodes::OP_TUCK:
                            if (count($mainStack) < 2) {
                                throw new \RuntimeException('Invalid stack operation OP_TUCK');
                            }
                            $vch = $mainStack[-1];
                            $mainStack->add(count($mainStack) - 1 - 2, $vch);
                            break;

                        case Opcodes::OP_PICK:
                        case Opcodes::OP_ROLL:
                            if (count($mainStack) < 2) {
                                throw new \RuntimeException('Invalid stack operation OP_PICK');
                            }

                            $n = Number::buffer($mainStack[-1], $minimal, 4)->getInt();
                            $mainStack->pop();
                            if ($math->cmp($n, 0) < 0 || $math->cmp($n, count($mainStack)) >= 0) {
                                throw new \RuntimeException('Invalid stack operation OP_PICK');
                            }

                            $pos = (int) $math->sub($math->sub(0, $n), 1);
                            $vch = $mainStack[$pos];
                            if ($opCode === Opcodes::OP_ROLL) {
                                unset($mainStack[$pos]);
                            }
                            $mainStack->push($vch);
                            break;

                        case Opcodes::OP_2DROP:
                            if (count($mainStack) < 2) {
                                throw new \RuntimeException('Invalid stack operation OP_2DROP');
                            }
                            $mainStack->pop();
                            $mainStack->pop();
                            break;

                        case Opcodes::OP_2DUP:
                            if (count($mainStack) < 2) {
                                throw new \RuntimeException('Invalid stack operation OP_2DUP');
                            }
                            $string1 = $mainStack[-2];
                            $string2 = $mainStack[-1];
                            $mainStack->push($string1);
                            $mainStack->push($string2);
                            break;

                        case Opcodes::OP_3DUP:
                            if (count($mainStack) < 3) {
                                throw new \RuntimeException('Invalid stack operation OP_3DUP');
                            }
                            $string1 = $mainStack[-3];
                            $string2 = $mainStack[-2];
                            $string3 = $mainStack[-1];
                            $mainStack->push($string1);
                            $mainStack->push($string2);
                            $mainStack->push($string3);
                            break;

                        case Opcodes::OP_2OVER:
                            if (count($mainStack) < 4) {
                                throw new \RuntimeException('Invalid stack operation OP_2OVER');
                            }
                            $string1 = $mainStack[-4];
                            $string2 = $mainStack[-3];
                            $mainStack->push($string1);
                            $mainStack->push($string2);
                            break;

                        case Opcodes::OP_2ROT:
                            if (count($mainStack) < 6) {
                                throw new \RuntimeException('Invalid stack operation OP_2ROT');
                            }
                            $string1 = $mainStack[-6];
                            $string2 = $mainStack[-5];
                            unset($mainStack[-6], $mainStack[-5]);
                            $mainStack->push($string1);
                            $mainStack->push($string2);
                            break;

                        case Opcodes::OP_2SWAP:
                            if (count($mainStack) < 4) {
                                throw new \RuntimeException('Invalid stack operation OP_2SWAP');
                            }
                            $mainStack->swap(-3, -1);
                            $mainStack->swap(-4, -2);
                            break;

                        case Opcodes::OP_SIZE:
                            if ($mainStack->isEmpty()) {
                                throw new \RuntimeException('Invalid stack operation OP_SIZE');
                            }
                            $size = Number::int($mainStack[-1]->getSize());
                            $mainStack->push($size->getBuffer());
                            break;

                        case Opcodes::OP_EQUAL:
                        case Opcodes::OP_EQUALVERIFY:
                            if (count($mainStack) < 2) {
                                throw new \RuntimeException('Invalid stack operation OP_EQUAL');
                            }

                            $equal = $mainStack[-2]->equals($mainStack[-1]);
                            $mainStack->pop();
                            $mainStack->pop();
                            $mainStack->push($equal ? $this->vchTrue : $this->vchFalse);
                            if ($opCode === Opcodes::OP_EQUALVERIFY) {
                                if ($equal) {
                                    $mainStack->pop();
                                } else {
                                    throw new \RuntimeException('Error EQUALVERIFY');
                                }
                            }

                            break;

                        // Arithmetic operations
                        case $opCode >= Opcodes::OP_1ADD && $opCode <= Opcodes::OP_0NOTEQUAL:
                            if ($mainStack->isEmpty()) {
                                throw new \Exception('Invalid stack operation 1ADD-OP_0NOTEQUAL');
                            }

                            $num = Number::buffer($mainStack[-1], $minimal)->getInt();

                            if ($opCode === Opcodes::OP_1ADD) {
                                $num = $math->add($num, '1');
                            } elseif ($opCode === Opcodes::OP_1SUB) {
                                $num = $math->sub($num, '1');
                            } elseif ($opCode === Opcodes::OP_2MUL) {
                                $num = $math->mul(2, $num);
                            } elseif ($opCode === Opcodes::OP_NEGATE) {
                                $num = $math->sub(0, $num);
                            } elseif ($opCode === Opcodes::OP_ABS) {
                                if ($math->cmp($num, '0') < 0) {
                                    $num = $math->sub(0, $num);
                                }
                            } elseif ($opCode === Opcodes::OP_NOT) {
                                $num = (int) $math->cmp($num, '0') === 0;
                            } else {
                                // is OP_0NOTEQUAL
                                $num = (int) ($math->cmp($num, '0') !== 0);
                            }

                            $mainStack->pop();

                            $buffer = Number::int($num)->getBuffer();

                            $mainStack->push($buffer);
                            break;

                        case $opCode >= Opcodes::OP_ADD && $opCode <= Opcodes::OP_MAX:
                            if (count($mainStack) < 2) {
                                throw new \Exception('Invalid stack operation (OP_ADD - OP_MAX)');
                            }

                            $num1 = Number::buffer($mainStack[-2], $minimal)->getInt();
                            $num2 = Number::buffer($mainStack[-1], $minimal)->getInt();

                            if ($opCode === Opcodes::OP_ADD) {
                                $num = $math->add($num1, $num2);
                            } else if ($opCode === Opcodes::OP_SUB) {
                                $num = $math->sub($num1, $num2);
                            } else if ($opCode === Opcodes::OP_BOOLAND) {
                                $num = $math->cmp($num1, '0') !== 0 && $math->cmp($num2, '0') !== 0;
                            } else if ($opCode === Opcodes::OP_BOOLOR) {
                                $num = $math->cmp($num1, '0') !== 0 || $math->cmp($num2, '0') !== 0;
                            } elseif ($opCode === Opcodes::OP_NUMEQUAL) {
                                $num = $math->cmp($num1, $num2) === 0;
                            } elseif ($opCode === Opcodes::OP_NUMEQUALVERIFY) {
                                $num = $math->cmp($num1, $num2) === 0;
                            } elseif ($opCode === Opcodes::OP_NUMNOTEQUAL) {
                                $num = $math->cmp($num1, $num2) !== 0;
                            } elseif ($opCode === Opcodes::OP_LESSTHAN) {
                                $num = $math->cmp($num1, $num2) < 0;
                            } elseif ($opCode === Opcodes::OP_GREATERTHAN) {
                                $num = $math->cmp($num1, $num2) > 0;
                            } elseif ($opCode === Opcodes::OP_LESSTHANOREQUAL) {
                                $num = $math->cmp($num1, $num2) <= 0;
                            } elseif ($opCode === Opcodes::OP_GREATERTHANOREQUAL) {
                                $num = $math->cmp($num1, $num2) >= 0;
                            } elseif ($opCode === Opcodes::OP_MIN) {
                                $num = ($math->cmp($num1, $num2) <= 0) ? $num1 : $num2;
                            } else {
                                $num = ($math->cmp($num1, $num2) >= 0) ? $num1 : $num2;
                            }

                            $mainStack->pop();
                            $mainStack->pop();
                            $buffer = Number::int($num)->getBuffer();
                            $mainStack->push($buffer);

                            if ($opCode === Opcodes::OP_NUMEQUALVERIFY) {
                                if ($this->castToBool($mainStack[-1])) {
                                    $mainStack->pop();
                                } else {
                                    throw new \RuntimeException('NUM EQUAL VERIFY error');
                                }
                            }
                            break;

                        case Opcodes::OP_WITHIN:
                            if (count($mainStack) < 3) {
                                throw new \RuntimeException('Invalid stack operation');
                            }

                            $num1 = Number::buffer($mainStack[-3], $minimal)->getInt();
                            $num2 = Number::buffer($mainStack[-2], $minimal)->getInt();
                            $num3 = Number::buffer($mainStack[-1], $minimal)->getInt();

                            $value = $math->cmp($num2, $num1) <= 0 && $math->cmp($num1, $num3) < 0;
                            $mainStack->pop();
                            $mainStack->pop();
                            $mainStack->pop();
                            $mainStack->push($value ? $this->vchTrue : $this->vchFalse);
                            break;

                        // Hash operation
                        case Opcodes::OP_RIPEMD160:
                        case Opcodes::OP_SHA1:
                        case Opcodes::OP_SHA256:
                        case Opcodes::OP_HASH160:
                        case Opcodes::OP_HASH256:
                            if ($mainStack->isEmpty()) {
                                throw new \RuntimeException('Invalid stack operation');
                            }

                            $buffer = $mainStack[-1];
                            if ($opCode === Opcodes::OP_RIPEMD160) {
                                $hash = Hash::ripemd160($buffer);
                            } elseif ($opCode === Opcodes::OP_SHA1) {
                                $hash = Hash::sha1($buffer);
                            } elseif ($opCode === Opcodes::OP_SHA256) {
                                $hash = Hash::sha256($buffer);
                            } elseif ($opCode === Opcodes::OP_HASH160) {
                                $hash = Hash::sha256ripe160($buffer);
                            } else {
                                $hash = Hash::sha256d($buffer);
                            }

                            $mainStack->pop();
                            $mainStack->push($hash);
                            break;

                        case Opcodes::OP_CODESEPARATOR:
                            $hashStartPos = $parser->getPosition();
                            break;

                        case Opcodes::OP_CHECKSIG:
                        case Opcodes::OP_CHECKSIGVERIFY:
                            if (count($mainStack) < 2) {
                                throw new \RuntimeException('Invalid stack operation');
                            }

                            $vchPubKey = $mainStack[-1];
                            $vchSig = $mainStack[-2];

                            $scriptCode = new Script($script->getBuffer()->slice($hashStartPos));

                            $success = $checker->checkSig($scriptCode, $vchSig, $vchPubKey, $sigVersion, $flags);

                            $mainStack->pop();
                            $mainStack->pop();
                            $mainStack->push($success ? $this->vchTrue : $this->vchFalse);

                            if ($opCode === Opcodes::OP_CHECKSIGVERIFY) {
                                if ($success) {
                                    $mainStack->pop();
                                } else {
                                    throw new \RuntimeException('Checksig verify');
                                }
                            }
                            break;

                        case Opcodes::OP_CHECKMULTISIG:
                        case Opcodes::OP_CHECKMULTISIGVERIFY:
                            $i = 1;
                            if (count($mainStack) < $i) {
                                throw new \RuntimeException('Invalid stack operation');
                            }

                            $keyCount = Number::buffer($mainStack[-$i], $minimal)->getInt();
                            if ($math->cmp($keyCount, 0) < 0 || $math->cmp($keyCount, 20) > 0) {
                                throw new \RuntimeException('OP_CHECKMULTISIG: Public key count exceeds 20');
                            }

                            $this->opCount += $keyCount;
                            $this->checkOpcodeCount();

                            // Extract positions of the keys, and signatures, from the stack.
                            $ikey = ++$i;
                            $i += $keyCount; /** @var int $i */
                            if (count($mainStack) < $i) {
                                throw new \RuntimeException('Invalid stack operation');
                            }

                            $sigCount = Number::buffer($mainStack[-$i], $minimal)->getInt();
                            if ($math->cmp($sigCount, 0) < 0 || $math->cmp($sigCount, $keyCount) > 0) {
                                throw new \RuntimeException('Invalid Signature count');
                            }

                            $isig = ++$i;
                            $i += $sigCount;

                            // Extract the script since the last OP_CODESEPARATOR
                            $scriptCode = new Script($script->getBuffer()->slice($hashStartPos));

                            $fSuccess = true;
                            while ($fSuccess && $sigCount > 0) {
                                // Fetch the signature and public key
                                $sig = $mainStack[-$isig];
                                $pubkey = $mainStack[-$ikey];

                                if ($checker->checkSig($scriptCode, $sig, $pubkey, $sigVersion, $flags)) {
                                    $isig++;
                                    $sigCount--;
                                }

                                $ikey++;
                                $keyCount--;

                                // If there are more signatures left than keys left,
                                // then too many signatures have failed. Exit early,
                                // without checking any further signatures.
                                if ($sigCount > $keyCount) {
                                    $fSuccess = false;
                                }
                            }

                            while ($i-- > 1) {
                                $mainStack->pop();
                            }

                            // A bug causes CHECKMULTISIG to consume one extra argument
                            // whose contents were not checked in any way.
                            //
                            // Unfortunately this is a potential source of mutability,
                            // so optionally verify it is exactly equal to zero prior
                            // to removing it from the stack.
                            if ($mainStack->isEmpty()) {
                                throw new \RuntimeException('Invalid stack operation');
                            }

                            if ($flags & self::VERIFY_NULL_DUMMY && $mainStack[-1]->getSize() !== 0) {
                                throw new ScriptRuntimeException(self::VERIFY_NULL_DUMMY, 'Extra P2SH stack value should be OP_0');
                            }

                            $mainStack->pop();
                            $mainStack->push($fSuccess ? $this->vchTrue : $this->vchFalse);

                            if ($opCode === Opcodes::OP_CHECKMULTISIGVERIFY) {
                                if ($fSuccess) {
                                    $mainStack->pop();
                                } else {
                                    throw new \RuntimeException('OP_CHECKMULTISIG verify');
                                }
                            }
                            break;

                        default:
                            throw new \RuntimeException('Opcode not found');
                    }

                    if (count($mainStack) + count($altStack) > 1000) {
                        throw new \RuntimeException('Invalid stack size, exceeds 1000');
                    }
                }
            }

            if (!$vfStack->end() === 0) {
                throw new \RuntimeException('Unbalanced conditional at script end');
            }

            return true;
        } catch (ScriptRuntimeException $e) {
            // echo "\n Runtime: " . $e->getMessage() . "\n";
            // Failure due to script tags, can access flag: $e->getFailureFlag()
            return false;
        } catch (\Exception $e) {
            // echo "\n General: " . $e->getMessage()  . PHP_EOL . $e->getTraceAsString();
            return false;
        }
    }
}
