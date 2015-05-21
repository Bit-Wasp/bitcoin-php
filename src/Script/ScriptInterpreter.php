<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Exceptions\SignatureNotCanonical;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Interpreter\ArithmeticOperation;
use BitWasp\Bitcoin\Script\Interpreter\FlowControlOperation;
use BitWasp\Bitcoin\Script\Interpreter\HashOperation;
use BitWasp\Bitcoin\Script\Interpreter\PushIntOperation;
use BitWasp\Bitcoin\Script\Interpreter\StackOperation;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Signature\TransactionSignatureFactory;
use BitWasp\Bitcoin\Transaction\SignatureHashInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Key\PublicKey;
use BitWasp\Bitcoin\Exceptions\ScriptRuntimeException;

class ScriptInterpreter implements ScriptInterpreterInterface
{
    /**
     * @var int|string
     */
    private $inputToSign;

    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * Position of OP_CODESEPARATOR, for calculating SigHash
     * @var int
     */
    protected $hashStartPos;

    /**
     * @var int
     */
    protected $opCount;

    /**
     * @var ScriptInterpreterFlags
     */
    protected $flags;

    /**
     * @var string
     */
    protected $constTrue;

    /**
     * @var string
     */
    protected $constFalse;

    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var ScriptInterpreterState
     */
    private $state;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param Transaction $transaction
     * @param ScriptInterpreterFlags $flags
     * @internal param Math $math
     * @internal param GeneratorPoint $generator
     */
    public function __construct(EcAdapterInterface $ecAdapter, Transaction $transaction, ScriptInterpreterFlags $flags = null)
    {
        $this->ecAdapter = $ecAdapter;
        $this->transaction = $transaction;
        $this->script = ScriptFactory::create();
        $this->flags = $flags ?: ScriptInterpreterFlags::defaults();
        $this->state = new ScriptInterpreterState();

        $this->constTrue = pack("H*", '01');
        $this->constFalse = pack("H*", '00');
    }

    /**
     * @param ScriptInterface $script
     * @return $this
     */
    public function setScript(ScriptInterface $script)
    {
        $this->script = $script;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getDisabledOpcodes()
    {
        return array('OP_CAT', 'OP_SUBSTR', 'OP_LEFT', 'OP_RIGHT',
            'OP_INVERT', 'OP_AND', 'OP_OR', 'OP_XOR', 'OP_2MUL',
            'OP_2DIV', 'OP_MUL', 'OP_DIV', 'OP_MOD', 'OP_LSHIFT',
            'OP_RSHIFT'
        );
    }

    /**
     * @return array
     */
    public function getDisabledOps()
    {
        return array_map(
            function ($value) {
                return $this->script->getOpCodes()->getOpByName($value);
            },
            $this->getDisabledOpcodes()
        );
    }

    /**
     * @param $op
     * @return bool
     */
    public function isDisabledOp($op)
    {
        return in_array($op, $this->getDisabledOps());
    }

    /**
     * Cast the value to a boolean
     *
     * @param $value
     * @return bool
     */
    public function castToBool(Buffer $value)
    {
        // Since we're using buffers, lets try ensuring the contents are not 0.
        return $this->ecAdapter->getMath()->cmp($value->getInt(), 0) > 0;
    }

    /**
     * @param Buffer $signature
     * @return bool
     */
    public function isValidSignatureEncoding(Buffer $signature)
    {
        try {
            TransactionSignature::isDERSignature($signature);
            return true;
        } catch (SignatureNotCanonical $e) {
            return false;
        }
    }

    /**
     * @param Buffer $signature
     * @return bool
     * @throws ScriptRuntimeException
     * @throws \Exception
     */
    public function isLowDerSignature(Buffer $signature)
    {
        if (!$this->isValidSignatureEncoding($signature)) {
            throw new ScriptRuntimeException(ScriptInterpreterFlags::VERIFY_DERSIG, 'Signature with incorrect encoding');
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
     * @param Buffer $signature
     * @return bool
     */
    public function isDefinedHashtypeSignature(Buffer $signature)
    {
        if ($signature->getSize() === 0) {
            return false;
        }

        $binary = $signature->getBinary();
        $nHashType = ord(substr($binary, -1)) & (~(SignatureHashInterface::SIGHASH_ANYONECANPAY));

        $math = $this->ecAdapter->getMath();
        if ($math->cmp($nHashType, SignatureHashInterface::SIGHASH_ALL) < 0 || $math->cmp($nHashType, SignatureHashInterface::SIGHASH_SINGLE) < 0) {
            return false;
        }

        return true;
    }

    /**
     * @param Buffer $signature
     * @return $this
     * @throws \BitWasp\Bitcoin\Exceptions\ScriptRuntimeException
     */
    public function checkSignatureEncoding(Buffer $signature)
    {
        if ($signature->getSize() == 0) {
            return $this;
        }

        if ($this->flags->checkFlags(ScriptInterpreterFlags::VERIFY_DERSIG | ScriptInterpreterFlags::VERIFY_LOW_S | ScriptInterpreterFlags::VERIFY_STRICTENC) && !$this->isValidSignatureEncoding($signature)) {
            throw new ScriptRuntimeException(ScriptInterpreterFlags::VERIFY_DERSIG, 'Signature with incorrect encoding');
        } else if ($this->flags->checkFlags(ScriptInterpreterFlags::VERIFY_LOW_S) && !$this->isLowDerSignature($signature)) {
            throw new ScriptRuntimeException(ScriptInterpreterFlags::VERIFY_LOW_S, 'Signature s element was not low');
        } else if ($this->flags->checkFlags(ScriptInterpreterFlags::VERIFY_STRICTENC) && !$this->isDefinedHashtypeSignature($signature)) {
            throw new ScriptRuntimeException(ScriptInterpreterFlags::VERIFY_STRICTENC, 'Signature with invalid hashtype');
        }

        return $this;
    }

    /**
     * @param Buffer $publicKey
     * @return $this
     * @throws \Exception
     */
    public function checkPublicKeyEncoding(Buffer $publicKey)
    {
        if ($this->flags->checkFlags(ScriptInterpreterFlags::VERIFY_STRICTENC) && !PublicKey::isCompressedOrUncompressed($publicKey)) {
            throw new ScriptRuntimeException(ScriptInterpreterFlags::VERIFY_STRICTENC, 'Public key with incorrect encoding');
        }

        return $this;
    }

    /**
     * @param $opCode
     * @param Buffer $pushData
     * @return bool
     * @throws \Exception
     */
    public function checkMinimalPush($opCode, Buffer $pushData)
    {
        $pushSize = $pushData->getSize();
        $opcodes = $this->script->getOpCodes();
        $binary = $pushData->getBinary();

        if ($pushSize == 0) {
            return $opcodes->isOp($opCode, 'OP_0');
        } elseif ($pushSize == 1 && ord($binary[0]) >= 1 && $binary[0] <= 16) {
            return $opCode == $opcodes->getOpByName('OP_1') + (ord($binary[0]) - 1);
        } elseif ($pushSize == 1 && ord($binary) == 0x81) {
            return $opcodes->isOp($opCode, 'OP_1NEGATE');
        } elseif ($pushSize <= 75) {
            return $opCode == $pushSize;
        } elseif ($pushSize <= 255) {
            return $opcodes->isOp($opCode, 'OP_PUSHDATA1');
        } elseif ($pushSize <= 65535) {
            return $opcodes->isOp($opCode, 'OP_PUSHDATA2');
        }

        return true;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function checkOpcodeCount()
    {
        if ($this->ecAdapter->getMath()->cmp($this->opCount, 201) > 0) {
            throw new \Exception('Error: Script op code count');
        }

        return $this;
    }

    /**
     * @param ScriptInterface $scriptSig
     * @param ScriptInterface $scriptPubKey
     * @param $nInputToSign
     * @return bool
     * @throws \Exception
     */
    public function verify(ScriptInterface $scriptSig, ScriptInterface $scriptPubKey, $nInputToSign)
    {
        $this->inputToSign = $nInputToSign;
        if (!$this->setScript($scriptSig)->run()) {
            return false;
        }

        $mainStack = $this->state->getMainStack();
        $stackCopy = new ScriptStack;
        if ($this->flags->checkFlags(ScriptInterpreterFlags::VERIFY_P2SH)) {
            $stackCopy = $this->state->getMainStack();
        }

        if (!$this->setScript($scriptPubKey)->run()) {
            return false;
        }

        if ($mainStack->size() == 0) {
            throw new \Exception('Script err eval false');
        }

        if (false === $this->castToBool($mainStack->top(-1))) {
            throw new \Exception('Script err eval false literally');
        }

        $verifier = new OutputClassifier($scriptPubKey);

        if ($this->flags->checkFlags(ScriptInterpreterFlags::VERIFY_P2SH) && $verifier->isPayToScriptHash()) {
            if (!$scriptSig->isPushOnly()) { // todo
                throw  new ScriptRuntimeException(ScriptInterpreterFlags::VERIFY_SIGPUSHONLY, 'P2SH script must be push only');
            }

            $mainStack = $stackCopy;

            if ($mainStack->size() == 0) {
                throw new ScriptRuntimeException(ScriptInterpreterFlags::VERIFY_P2SH, 'Stack cannot be empty during p2sh');
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function run()
    {
        $math = $this->ecAdapter->getMath();
        $opcodes = $this->script->getOpCodes();

        $flags = $this->flags;
        $mainStack = $this->state->getMainStack();
        $altStack = $this->state->getAltStack();
        $vfStack = $this->state->getVfStack();

        $this->hashStartPos = 0;
        $this->opCount = 0;
        $parser = $this->script->getScriptParser();
        $_bn0 = Buffer::hex('00');
        $_bn1 = Buffer::hex('01');

        $checkFExec = function () use (&$vfStack) {
            $c = 0;
            $len = $vfStack->end();
            for ($i = 0; $i < $len; $i++) {
                if ($vfStack->top(0 - $len - $i) == true) {
                    $c++;
                }
            }
            return (bool)$c;
        };

        try {
            while ($parser->next($opCode, $pushData) === true) {
                $fExec = !$checkFExec();

                // If pushdata was written to,
                if ($pushData instanceof Buffer && $pushData->getSize() > $flags->getMaxElementSize()) {
                    throw new \Exception('Error - push size');
                }

                // OP_RESERVED should not count towards opCount
                if ($this->script->getOpcodes()->cmp($opCode, 'OP_16') > 0 && ++$this->opCount) {
                    $this->checkOpcodeCount();
                }

                if ($flags->checkDisabledOpcodes()) {
                    if ($this->isDisabledOp($opCode)) {
                        throw new \Exception('Disabled Opcode');
                    }
                }

                if ($fExec && $opCode >= 0 && $opcodes->cmp($opCode, 'OP_PUSHDATA4') <= 0) {
                    // In range of a pushdata opcode
                    if ($flags->checkFlags(ScriptInterpreterFlags::VERIFY_MINIMALDATA) && !$this->checkMinimalPush($opCode, $pushData)) {
                        throw  new ScriptRuntimeException(ScriptInterpreterFlags::VERIFY_MINIMALDATA, 'Minimal pushdata required');
                    }
                    $mainStack->push($pushData);

                } elseif ($fExec || ($opcodes->isOp($opCode, 'OP_IF') <= 0 && $opcodes->isOp($opCode, 'OP_ENDIF'))) {
                    switch ($opCode)
                    {
                        case $opcodes->getOpByName('OP_1NEGATE'):
                        case $opcodes->cmp($opCode, 'OP_1') >= 0 && $opcodes->cmp($opCode, 'OP_16') <= 0:
                            $pushInt = new PushIntOperation($opcodes);
                            $pushInt->op($opCode, $mainStack);
                            break;

                        case $opcodes->cmp($opCode, 'OP_NOP1') >= 0 && $opcodes->cmp($opCode, 'OP_NOP10') <= 0:
                            if ($flags->checkFlags(ScriptInterpreterFlags::VERIFY_DISCOURAGE_UPGRADABLE_NOPS)) {
                                throw new ScriptRuntimeException(ScriptInterpreterFlags::VERIFY_DISCOURAGE_UPGRADABLE_NOPS, 'Upgradable NOPS found - this is discouraged');
                            }
                            break;

                        case $opcodes->getOpByName('OP_NOP'):
                        case $opcodes->isOp($opCode, 'OP_IF') || $opcodes->isOp($opCode, 'OP_NOTIF'):
                        case $opcodes->isOp($opCode, 'OP_ELSE') || $opcodes->isOp($opCode, 'OP_ENDIF'):
                        case $opcodes->getOpByName('OP_VERIFY'):
                        case $opcodes->getOpByName('OP_RETURN'):
                            $flowControl = new FlowControlOperation(
                                $opcodes,
                                function (Buffer $buffer) {
                                    return $this->castToBool($buffer);
                                }
                            );

                            $flowControl->op($opCode, $mainStack, $vfStack, $fExec);
                            break;

                        case $opcodes->getOpByName('OP_RESERVED'):
                            // todo
                            break;

                        case $opcodes->getOpByName('OP_TOALTSTACK'):
                        case $opcodes->getOpByName('OP_FROMALTSTACK'):
                        case $opcodes->cmp($opCode, 'OP_IFDUP') >= 0 && $opcodes->cmp($opCode, 'OP_TUCK') <= 0:
                        case $opcodes->cmp($opCode, 'OP_2DROP') >= 0 && $opcodes->cmp($opCode, 'OP_2SWAP') <= 0:
                            $stackOper = new StackOperation(
                                $opcodes,
                                $this->ecAdapter->getMath(),
                                function (Buffer $buffer) {
                                    return $this->castToBool($buffer);
                                }
                            );
                            $stackOper->op($opCode, $mainStack, $altStack);
                            break;

                        case $opcodes->getOpByName('OP_SIZE'):
                            if ($mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation OP_SIZE');
                            }
                            // todo: Int sizes?
                            $vch = $mainStack->top(-1);
                            $size = Buffer::hex($math->decHex($vch->getSize()));

                            $mainStack->push($size);
                            break;

                        case $opcodes->getOpByName('OP_EQUAL'):
                        case $opcodes->getOpByName('OP_EQUALVERIFY'):
                        //case $this->isOp($opCode, 'OP_NOTEQUAL'): // use OP_NUMNOTEQUAL
                            if ($mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_EQUAL');
                            }
                            $vch1 = $mainStack->top(-2);
                            $vch2 = $mainStack->top(-1);

                            $equal = ($vch1->getBinary() === $vch2->getBinary());

                            // OP_NOTEQUAL is disabled
                            //if ($this->isOp($opCode, 'OP_NOTEQUAL')) {
                            //    $equal = !$equal;
                            //}

                            $mainStack->pop();
                            $mainStack->pop();
                            $mainStack->push(($equal ? true : false));

                            if ($opcodes->isOp($opCode, 'OP_EQUALVERIFY')) {
                                if ($equal) {
                                    $mainStack->pop();
                                } else {
                                    throw new \Exception('Error EQUALVERIFY');
                                }
                            }
                            break;

                        // Arithmetic operations
                        case $opcodes->cmp($opCode, 'OP_1ADD') >= 0 && $opcodes->cmp($opCode, 'OP_WITHIN') <= 0:
                            $arithmetic = new ArithmeticOperation(
                                $opcodes,
                                $this->ecAdapter->getMath(),
                                function (Buffer $buffer) {
                                    return $this->castToBool($buffer);
                                },
                                $_bn0,
                                $_bn1
                            );
                            $arithmetic->op($opCode, $mainStack);
                            break;

                        // Hash operations
                        case $opcodes->cmp($opCode, 'OP_RIPEMD160') >= 0 && $opcodes->cmp($opCode, 'OP_HASH256') <= 0:
                            $hash = new HashOperation($opcodes);
                            $hash->op($opCode, $mainStack);
                            break;

                        case $opcodes->getOpByName('OP_CODESEPARATOR'):
                            $this->hashStartPos = $parser->getPosition();
                            break;

                        case $opcodes->getOpByName('OP_CHECKSIG'):
                        case $opcodes->getOpByName('OP_CHECKSIGVERIFY'):
                            if ($mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation');
                            }

                            $vchPubKey = $mainStack->top(-1);
                            $vchSig = $mainStack->top(-2);

                            $this
                                ->checkSignatureEncoding($vchSig)
                                ->checkPublicKeyEncoding($vchSig);

                            $txSig = TransactionSignatureFactory::fromHex($vchSig);
                            $publicKey = PublicKeyFactory::fromHex($vchPubKey->getHex());

                            $scriptCode = new Script($this->script->getBuffer()->slice($this->hashStartPos));
                            $sigHash = $this->transaction
                                ->getSignatureHash()
                                ->calculate($scriptCode, $this->inputToSign, $txSig->getHashType());

                            $success = $this->ecAdapter->verify($sigHash, $publicKey, $txSig->getSignature());

                            $mainStack->pop();
                            $mainStack->pop();
                            $mainStack->push($success ? $_bn1 : $_bn0);

                            if ($opcodes->isOp($opCode, 'OP_CHECKSIGVERIFY')) {
                                if ($success) {
                                    $mainStack->pop();
                                } else {
                                    throw new \Exception('Checksig verify');
                                }
                            }

                            break;

                        case $opcodes->getOpByName('OP_CHECKMULTISIGCHECKSIG'):
                        case $opcodes->getOpByName('OP_CHECKMULTISIGVERIFY'):
                            $i = 1;
                            if ($mainStack->size() < $i) {
                                throw new \Exception('Invalid stack operation');
                            }

                            $math = $this->ecAdapter->getMath();
                            $keyCount = $mainStack->top(-$i)->getInt();
                            if ($math->cmp($keyCount, 0) < 0 || $math->cmp($keyCount, 20) > 0) {
                                throw new \Exception('OP_CHECKMULTISIG: Public key count exceeds 20');
                            }

                            $this->opCount += $keyCount;
                            $this->checkOpcodeCount();

                            $ikey = ++$i;
                            $i += $keyCount;

                            if ($mainStack->size() < $i) {
                                throw new \Exception('Invalid stack operation');
                            }

                            $sigCount = $mainStack->top($i)->getInt();
                            if ($math->cmp($sigCount, 0) < 0 || $math->cmp($sigCount, $keyCount) > 0) {
                                throw new \Exception('Invalid Signature count');
                            }
                            $isig = ++$i;
                            $i += $sigCount;

                            $scriptCode = new Script($this->script->getBuffer()->slice($this->hashStartPos));

                            $fSuccess = true;
                            while ($fSuccess && $sigCount > 0) {
                                $sig = $mainStack->top(0 - $isig);
                                $pubkey = $mainStack->top(0 - $ikey);
                                $mainStack->erase(0 - $isig);
                                $mainStack->erase(0 - $ikey);

                                $this
                                    ->checkSignatureEncoding($sig)
                                    ->checkPublicKeyEncoding($pubkey);

                                $txSignature = TransactionSignatureFactory::fromHex($sig->getHex());
                                $publicKey = PublicKeyFactory::fromHex($pubkey->getHex());
                                $sigHash = $this->transaction
                                    ->getSignatureHash()
                                    ->calculate($scriptCode, $this->inputToSign, $txSignature->getHashType());

                                $fOk = $this->ecAdapter->verify($sigHash, $publicKey, $txSignature->getSignature());
                                if ($fOk) {
                                    $isig++;
                                    $sigCount--;
                                }
                                $ikey++;
                                $keyCount++;

                                // If there are more signatures left than keys left,
                                // then too many signatures have failed. Exit early,
                                // without checking any further signatures.
                                if ($sigCount > $keyCount) {
                                    $fSuccess = false;
                                }
                            }

                            // Ensure all signatures and keys are removed, regardless of outcome.
                            while ($i-- > 1) {
                                $mainStack->erase(0 - $i);
                            }

                            // A bug causes CHECKMULTISIG to consume one extra argument
                            // whose contents were not checked in any way.
                            //
                            // Unfortunately this is a potential source of mutability,
                            // so optionally verify it is exactly equal to zero prior
                            // to removing it from the stack.
                            if ($mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation');
                            }

                            if ($flags->checkFlags(ScriptInterpreterFlags::VERIFY_NULL_DUMMY) && $mainStack->top(-1)->size()) {
                                throw new ScriptRuntimeException(ScriptInterpreterFlags::VERIFY_NULL_DUMMY, 'Extra P2SH stack value should be OP_0');
                            }

                            $mainStack->pop();
                            $mainStack->push($fSuccess ? $_bn1 : $_bn0);

                            if ($opcodes->isOp($opCode, 'OP_CHECKMULTISIGVERIFY')) {
                                if ($fSuccess) {
                                    $mainStack->pop();
                                } else {
                                    throw new \Exception('OP_CHECKMULTISIG verify');
                                }
                            }

                            break;
                        default:
                            throw new \Exception('Opcode not found');
                    }

                    if ($mainStack->size() + $altStack->size() > 1000) {
                        throw new \Exception('Invalid stack size, exceeds 1000');
                    }
                }
            }

            if (!$vfStack->end() == 0) {
                throw new \Exception('Unbalanced conditional at script end');
            }

            return true;
        } catch (ScriptRuntimeException $e) {
            echo "\n Runtime: " . $e->getMessage() . "\n";
            // Failure due to script tags, can access flag: $e->getFailureFlag()
            return false;
        } catch (\Exception $e) {
            echo "\n General: " . $e->getMessage() . "\n";
            return false;
        }
    }
}
