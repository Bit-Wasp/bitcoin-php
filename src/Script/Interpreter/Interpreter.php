<?php

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Exceptions\SignatureNotCanonical;
use BitWasp\Bitcoin\Exceptions\ScriptRuntimeException;
use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Locktime;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Signature\TransactionSignatureFactory;
use BitWasp\Bitcoin\Transaction\SignatureHash\SignatureHashInterface;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class Interpreter implements InterpreterInterface
{
    /**
     * @var int|string
     */
    private $inputToSign;

    /**
     * @var TransactionInterface
     */
    private $transaction;

    /**
     * Position of OP_CODESEPARATOR, for calculating SigHash
     * @var int
     */
    private $hashStartPos;

    /**
     * @var int
     */
    private $opCount;

    /**
     * @var \BitWasp\Bitcoin\Flags
     */
    private $flags;

    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var \BitWasp\Bitcoin\Math\Math
     */
    private $math;

    /**
     * @var Buffer
     */
    private $vchFalse;

    /**
     * @var Buffer
     */
    private $vchTrue;

    /**
     * @var Buffer
     */
    private $int0;

    /**
     * @var Buffer
     */
    private $int1;

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
     * @param TransactionInterface $transaction
     */
    public function __construct(EcAdapterInterface $ecAdapter, TransactionInterface $transaction)
    {
        $this->ecAdapter = $ecAdapter;
        $this->math = $ecAdapter->getMath();
        $this->transaction = $transaction;

        $this->vchFalse = new Buffer("", 0, $this->math);
        $this->vchTrue = new Buffer("\x01", 1, $this->math);
        $this->int0 = Number::buffer($this->vchFalse, false, 4, $this->math)->getBuffer();
        $this->int1 = Number::buffer($this->vchTrue, false, 1, $this->math)->getBuffer();
    }

    /**
     * Cast the value to a boolean
     *
     * @param BufferInterface $value
     * @return bool
     */
    public function castToBool(BufferInterface $value)
    {
        if ($value->getSize() === 0) {
            return true;
        }

        // Since we're using buffers, lets try ensuring the contents are not 0.
        return $this->math->cmp($value->getInt(), 0) > 0;
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
     * @param BufferInterface $signature
     * @return bool
     * @throws ScriptRuntimeException
     * @throws \Exception
     */
    public function isLowDerSignature(BufferInterface $signature)
    {
        if (!$this->isValidSignatureEncoding($signature)) {
            throw new ScriptRuntimeException(self::VERIFY_DERSIG, 'Signature with incorrect encoding');
        }

        $binary = $signature->getBinary();
        $nLenR = ord($binary[3]);
        $nLenS = ord($binary[5 + $nLenR]);
        $s = $signature->slice(6 + $nLenR, $nLenS)->getInt();

        return $this->ecAdapter->validateSignatureElement($s, true);
    }

    /**
     * Determine whether the sighash byte appended to the signature encodes
     * a valid sighash type.
     *
     * @param BufferInterface $signature
     * @return bool
     */
    public function isDefinedHashtypeSignature(BufferInterface $signature)
    {
        if ($signature->getSize() === 0) {
            return false;
        }

        $binary = $signature->getBinary();
        $nHashType = ord(substr($binary, -1)) & (~(SignatureHashInterface::SIGHASH_ANYONECANPAY));

        $math = $this->math;
        return ! ($math->cmp($nHashType, SignatureHashInterface::SIGHASH_ALL) < 0 || $math->cmp($nHashType, SignatureHashInterface::SIGHASH_SINGLE) > 0);
    }

    /**
     * @param BufferInterface $signature
     * @param int $flags
     * @return $this
     * @throws \BitWasp\Bitcoin\Exceptions\ScriptRuntimeException
     */
    public function checkSignatureEncoding(BufferInterface $signature, $flags)
    {
        if ($signature->getSize() === 0) {
            return $this;
        }

        if ($flags & (self::VERIFY_DERSIG | self::VERIFY_LOW_S | self::VERIFY_STRICTENC) && !$this->isValidSignatureEncoding($signature)) {
            throw new ScriptRuntimeException(self::VERIFY_DERSIG, 'Signature with incorrect encoding');
        } else if ($flags & self::VERIFY_LOW_S && !$this->isLowDerSignature($signature)) {
            throw new ScriptRuntimeException(self::VERIFY_LOW_S, 'Signature s element was not low');
        } else if ($flags & self::VERIFY_STRICTENC && !$this->isDefinedHashtypeSignature($signature)) {
            throw new ScriptRuntimeException(self::VERIFY_STRICTENC, 'Signature with invalid hashtype');
        }

        return $this;
    }

    /**
     * @param BufferInterface $publicKey
     * @param int $flags
     * @return $this
     * @throws \Exception
     */
    public function checkPublicKeyEncoding(BufferInterface $publicKey, $flags)
    {
        if ($flags & self::VERIFY_STRICTENC && !PublicKey::isCompressedOrUncompressed($publicKey)) {
            throw new ScriptRuntimeException(self::VERIFY_STRICTENC, 'Public key with incorrect encoding');
        }

        return $this;
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
     * @param ScriptInterface $script
     * @param BufferInterface $sigBuf
     * @param BufferInterface $keyBuf
     * @param int $flags
     * @return bool
     * @throws ScriptRuntimeException
     * @throws \Exception
     */
    private function checkSig(ScriptInterface $script, BufferInterface $sigBuf, BufferInterface $keyBuf, $flags)
    {
        $this
            ->checkSignatureEncoding($sigBuf, $flags)
            ->checkPublicKeyEncoding($keyBuf, $flags);

        try {
            $txSignature = TransactionSignatureFactory::fromHex($sigBuf->getHex());
            $publicKey = PublicKeyFactory::fromHex($keyBuf->getHex());

            return $this->ecAdapter->verify(
                $this->transaction->getSignatureHash()->calculate($script, $this->inputToSign, $txSignature->getHashType()),
                $publicKey,
                $txSignature->getSignature()
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param int $txLockTime
     * @param int $nThreshold
     * @param \BitWasp\Bitcoin\Script\Interpreter\Number $lockTime
     * @return bool
     */
    private function verifyLockTime($txLockTime, $nThreshold, \BitWasp\Bitcoin\Script\Interpreter\Number $lockTime)
    {
        $nTime = $lockTime->getInt();
        if (($this->math->cmp($txLockTime, $nThreshold) < 0 && $this->math->cmp($nTime, $nThreshold) < 0) ||
            ($this->math->cmp($txLockTime, $nThreshold) >= 0 && $this->math->cmp($nTime, $nThreshold) >= 0)
        ) {
            return false;
        }

        return $this->math->cmp($nTime, $txLockTime) >= 0;
    }

    /**
     * @param \BitWasp\Bitcoin\Script\Interpreter\Number $lockTime
     * @return bool
     */
    private function checkLockTime(\BitWasp\Bitcoin\Script\Interpreter\Number $lockTime)
    {
        if ($this->transaction->getInput($this->inputToSign)->isFinal()) {
            return false;
        }

        return $this->verifyLockTime($this->transaction->getLockTime(), Locktime::BLOCK_MAX, $lockTime);
    }

    /**
     * @param WitnessProgram $witnessProgram
     * @param ScriptWitness $witness
     * @param int $flags
     * @return bool
     */
    private function verifyWitnessProgram(WitnessProgram $witnessProgram, ScriptWitness $witness, $flags)
    {
        $version = $witnessProgram->getVersion();
        if ($version === 0) {
            $scriptPubKey = new Script($witnessProgram->getProgram());
            $stackValues = $witness->all();

        } elseif ($version === 1) {
            $program = $witnessProgram->getProgram();
            if ($program->getSize() !== 32) { // SCRIPT_ERR_WITNESS_PROGRAM_WRONG_LENGTH
                return false;
            }

            $count = count($witness);
            if ($count === 0) { // SCRIPT_ERR_WITNESS_PROGRAM_WITNESS_EMPTY
                return false;
            }

            $scriptPubKey = new Script($witness[$count - 1]);
            $stackValues = $witness->slice(0, -1);
            $hashScriptPubKey = Hash::sha256($witness[$count - 1]);
            if ($hashScriptPubKey->getBinary() == $program) {
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

        if (!$this->evaluate($scriptPubKey, $mainStack, $flags)) {
            return false;
        }

        if ($mainStack->count() !== 1) {
            return false;
        }

        if (!$this->castToBool($mainStack[count($mainStack) - 1])) {
            return false;
        }

        return true;
    }

    /**
     * @param \BitWasp\Bitcoin\Script\Interpreter\Number $sequence
     * @return bool
     */
    private function checkSequence(\BitWasp\Bitcoin\Script\Interpreter\Number $sequence)
    {
        $txSequence = $this->transaction->getInput($this->inputToSign)->getSequence();
        if ($this->transaction->getVersion() < 2) {
            return false;
        }

        if ($this->math->cmp($this->math->bitwiseAnd($txSequence, TransactionInputInterface::SEQUENCE_LOCKTIME_DISABLE_FLAG), 0) !== 0) {
            return 0;
        }

        $mask = $this->math->bitwiseOr(TransactionInputInterface::SEQUENCE_LOCKTIME_TYPE_FLAG, TransactionInputInterface::SEQUENCE_LOCKTIME_MASK);
        return $this->verifyLockTime(
            $this->math->bitwiseAnd($txSequence, $mask),
            TransactionInputInterface::SEQUENCE_LOCKTIME_TYPE_FLAG,
            Number::int($this->math->bitwiseAnd($sequence->getInt(), $mask))
        );
    }

    /**
     * @param ScriptInterface $scriptSig
     * @param ScriptInterface $scriptPubKey
     * @param int $flags
     * @param ScriptWitness $witness
     * @param int $nInputToSign
     * @return bool
     */
    public function verify(ScriptInterface $scriptSig, ScriptInterface $scriptPubKey, $nInputToSign, $flags, ScriptWitness $witness = null)
    {
        static $emptyWitness = null;
        if ($emptyWitness === null) {
            $emptyWitness = new ScriptWitness([]);
        }

        $witness = $witness ?: $emptyWitness;

        $this->inputToSign = $nInputToSign;

        $stack = new Stack();
        if (!$this->evaluate($scriptSig, $stack, $flags)) {
            return false;
        }

        $stackCopy = new Stack;
        if ($flags & self::VERIFY_P2SH) {
            $stackCopy = clone $stack;
        }

        if (!$this->evaluate($scriptPubKey, $stack, $flags)) {
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

                if (!$this->verifyWitnessProgram($program, $witness, $flags)) {
                    return false;
                }

                $stack->resize(1);
            }
        }

        if ($flags & self::VERIFY_P2SH && (new OutputClassifier($scriptPubKey))->isPayToScriptHash()) {
            if (!$scriptSig->isPushOnly()) {
                return false;
            }

            // Restore mainStack to how it was after evaluating scriptSig
            $stack = $stackCopy;
            if ($stack->isEmpty()) {
                return false;
            }

            // Load redeemscript as the scriptPubKey
            $scriptPubKey = new Script($stack->bottom());
            $stack->pop();

            if (!$this->evaluate($scriptPubKey, $stack, $flags)) {
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
                    if ($scriptSig != (ScriptFactory::create()->push($scriptPubKey->getBuffer())->getScript())) {
                        return false; // SCRIPT_ERR_WITNESS_MALLEATED_P2SH
                    }

                    if (!$this->verifyWitnessProgram($program, $witness, $flags)) {
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
     * @param int $flags
     * @return bool
     */
    public function evaluate(ScriptInterface $script, Stack $mainStack, $flags)
    {
        $math = $this->math;
        $this->hashStartPos = 0;
        $this->opCount = 0;
        $altStack = new Stack();
        $vfStack = new Stack();
        $parser = $script->getScriptParser();

        if ($script->getBuffer()->getSize() > 10000) {
            return false;
        }

        try {
            foreach ($parser as $operation) {
                $opCode = $operation->getOp();
                $pushData = $operation->getData();
                $fExec = $this->checkExec($vfStack);

                // If pushdata was written to,
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
                    if ($flags & self::VERIFY_MINIMALDATA && !$this->checkMinimalPush($opCode, $pushData)) {
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
                            $num = decodeOpN($opCode);
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

                            $lockTime = Number::buffer($mainStack[-1], $flags & self::VERIFY_MINIMALDATA, 5, $math);
                            if (!$this->checkLockTime($lockTime)) {
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

                            $sequence = Number::buffer($mainStack[-1], $flags & self::VERIFY_MINIMALDATA, 5, $math);
                            $nSequence = $sequence->getInt();
                            if ($math->cmp($nSequence, 0) < 0) {
                                throw new ScriptRuntimeException(self::VERIFY_CHECKSEQUENCEVERIFY, 'Negative locktime');
                            }

                            if ($math->cmp($math->bitwiseAnd($nSequence, TransactionInputInterface::SEQUENCE_LOCKTIME_DISABLE_FLAG), '0') !== 0) {
                                break;
                            }

                            if (!$this->checkSequence($sequence)) {
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
                                // todo
                                $buffer = Number::buffer($mainStack->pop(), $flags & self::VERIFY_MINIMALDATA)->getBuffer();
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
                            $vfStack[-1] = !$vfStack->end() ? $this->vchTrue : $this->vchFalse;
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

                        case Opcodes::OP_RESERVED:
                            // todo
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
                            if ($num === 0) {
                                $depth = $this->vchFalse;
                            } else {
                                $depth = Number::int($num)->getBuffer();
                            }

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

                            $n = Number::buffer($mainStack[-1], $flags & self::VERIFY_MINIMALDATA, 4)->getInt();
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
                            // todo: Int sizes?
                            $vch = $mainStack[-1];
                            $mainStack->push(Number::int($vch->getSize())->getBuffer());
                            break;

                        case Opcodes::OP_EQUAL:
                        case Opcodes::OP_EQUALVERIFY:
                            if (count($mainStack) < 2) {
                                throw new \RuntimeException('Invalid stack operation OP_EQUAL');
                            }
                            $vch1 = $mainStack[-2];
                            $vch2 = $mainStack[-1];

                            $equal = ($vch1->getBinary() === $vch2->getBinary());

                            $mainStack->pop();
                            $mainStack->pop();
                            $mainStack->push(($equal ? $this->vchTrue : $this->vchFalse));
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

                            $num = Number::buffer($mainStack[-1], $flags & self::VERIFY_MINIMALDATA)->getInt();

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

                            $num1 = Number::buffer($mainStack[-2], $flags & self::VERIFY_MINIMALDATA)->getInt();
                            $num2 = Number::buffer($mainStack[-1], $flags & self::VERIFY_MINIMALDATA)->getInt();

                            if ($opCode === Opcodes::OP_ADD) {
                                $num = $math->add($num1, $num2);
                            } else if ($opCode === Opcodes::OP_SUB) {
                                $num = $math->sub($num1, $num2);
                            } else if ($opCode === Opcodes::OP_BOOLAND) {
                                $num = $math->cmp($num1, $this->int0->getInt()) !== 0 && $math->cmp($num2, $this->int0->getInt()) !== 0;
                            } else if ($opCode === Opcodes::OP_BOOLOR) {
                                $num = $math->cmp($num1, $this->int0->getInt()) !== 0 || $math->cmp($num2, $this->int0->getInt()) !== 0;
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

                            $num1 = Number::buffer($mainStack[-3], $flags & self::VERIFY_MINIMALDATA)->getInt();
                            $num2 = Number::buffer($mainStack[-2], $flags & self::VERIFY_MINIMALDATA)->getInt();
                            $num3 = Number::buffer($mainStack[-1], $flags & self::VERIFY_MINIMALDATA)->getInt();

                            $value = $math->cmp($num2, $num1) <= 0 && $math->cmp($num1, $num3) < 0;
                            $mainStack->pop();
                            $mainStack->pop();
                            $mainStack->pop();
                            $mainStack->push($value ? $this->vchFalse : $this->vchTrue);
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
                            $this->hashStartPos = $parser->getPosition();
                            break;

                        case Opcodes::OP_CHECKSIG:
                        case Opcodes::OP_CHECKSIGVERIFY:
                            if (count($mainStack) < 2) {
                                throw new \RuntimeException('Invalid stack operation');
                            }

                            $vchPubKey = $mainStack[-1];
                            $vchSig = $mainStack[-2];

                            $scriptCode = new Script($script->getBuffer()->slice($this->hashStartPos));
                            $success = $this->checkSig($scriptCode, $vchSig, $vchPubKey, $flags);

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

                            $keyCount = Number::buffer($mainStack[-$i], $flags & self::VERIFY_MINIMALDATA)->getInt();
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

                            $sigCount = Number::buffer($mainStack[-$i], $flags & self::VERIFY_MINIMALDATA)->getInt();
                            if ($math->cmp($sigCount, 0) < 0 || $math->cmp($sigCount, $keyCount) > 0) {
                                throw new \RuntimeException('Invalid Signature count');
                            }
                            $isig = ++$i;
                            $i += $sigCount;

                            // Extract the script since the last OP_CODESEPARATOR
                            $scriptCode = new Script($script->getBuffer()->slice($this->hashStartPos));

                            $fSuccess = true;
                            while ($fSuccess && $sigCount > 0) {
                                // Fetch the signature and public key
                                $sig = $mainStack[-$isig];
                                $pubkey = $mainStack[-$ikey];

                                // Erase the signature and public key.
                                unset($mainStack[-$isig], $mainStack[-$ikey]);

                                // Decrement $i, since we are consuming stack values.
                                $i -= 2;

                                if ($this->checkSig($scriptCode, $sig, $pubkey, $flags)) {
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
            // echo "\n General: " . $e->getMessage() ;
            return false;
        }
    }
}
