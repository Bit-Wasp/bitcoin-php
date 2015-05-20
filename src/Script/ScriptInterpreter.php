<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Exceptions\SignatureNotCanonical;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
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
     * @param $op
     * @return bool
     */
    public function isPushOp($op)
    {
        if (is_numeric($op)) {
            return ($op > 0 && $op <= 96);
        } else {
            return false;
        }
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

    public function isValidSignatureEncoding(Buffer $signature)
    {
        try {
            TransactionSignature::isDERSignature($signature);
            return true;
        } catch (SignatureNotCanonical $e) {
            return false;
        }
    }

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
     * @param ScriptInterface $script
     * @return $this
     */
    public function setScript(ScriptInterface $script)
    {
        $this->script = $script;
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

        $this->opCount = 0;
        $parser = $this->script->getScriptParser();
        $_bn0 = Buffer::hex('00');
        $_bn1 = Buffer::hex('01');

        $checkFExec = function () use (&$vfStack) {
            $c = 0;
            for ($i = 0, $len = $vfStack->end(); $i < $len; $i++) {
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
                if ($this->script->getOpcodes()->cmp($opCode, 'OP_16') > 0 && ++$this->opCount > 201) {
                    throw new \Exception('Error - Script Op Count');
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
                        case $opcodes->getOpByName('OP_1'):
                        case $opcodes->getOpByName('OP_2'):
                        case $opcodes->getOpByName('OP_3'):
                        case $opcodes->getOpByName('OP_4'):
                        case $opcodes->getOpByName('OP_5'):
                        case $opcodes->getOpByName('OP_6'):
                        case $opcodes->getOpByName('OP_7'):
                        case $opcodes->getOpByName('OP_8'):
                        case $opcodes->getOpByName('OP_9'):
                        case $opcodes->getOpByName('OP_10'):
                        case $opcodes->getOpByName('OP_11'):
                        case $opcodes->getOpByName('OP_12'):
                        case $opcodes->getOpByName('OP_13'):
                        case $opcodes->getOpByName('OP_14'):
                        case $opcodes->getOpByName('OP_15'):
                        case $opcodes->getOpByName('OP_16'):
                            $num = $opCode - ($opcodes->getOpByName('OP_1') - 1);
                            $mainStack->push($num);
                            break;

                        case $opcodes->getOpByName('OP_NOP'):
                            break;

                        case $opcodes->getOpByName('OP_NOP1'):
                        case $opcodes->getOpByName('OP_NOP2'):
                        case $opcodes->getOpByName('OP_NOP3'):
                        case $opcodes->getOpByName('OP_NOP4'):
                        case $opcodes->getOpByName('OP_NOP5'):
                        case $opcodes->getOpByName('OP_NOP6'):
                        case $opcodes->getOpByName('OP_NOP7'):
                        case $opcodes->getOpByName('OP_NOP8'):
                        case $opcodes->getOpByName('OP_NOP9'):
                        case $opcodes->getOpByName('OP_NOP10'):
                            if ($flags->checkFlags(ScriptInterpreterFlags::VERIFY_DISCOURAGE_UPGRADABLE_NOPS)) {
                                throw new ScriptRuntimeException(ScriptInterpreterFlags::VERIFY_DISCOURAGE_UPGRADABLE_NOPS, 'Upgradable NOPS found - this is discouraged');
                            }
                            break;

                        case $opcodes->getOpByName('OP_IF'):
                        case $opcodes->getOpByName('OP_NOTIF'):
                            // <expression> if [statements] [else [statements]] endif
                            $value = false;
                            if ($fExec) {
                                if ($mainStack->size() < 1) {
                                    throw new \Exception('Unbalanced conditional');
                                }
                                // todo
                                $buffer = $mainStack->pop();
                                $value = $this->castToBool($buffer);
                                if ($opcodes->isOp($opCode, 'OP_NOTIF')) {
                                    $value = !$value;
                                }
                            }
                            $vfStack->push($value);
                            break;

                        case $opcodes->getOpByName('OP_ELSE'):
                            if ($vfStack->size() == 0) {
                                throw new \Exception('Unbalanced conditional');
                            }
                            $vfStack->set($vfStack->end() - 1, !$vfStack->end());
                            break;

                        case $opcodes->getOpByName('OP_ENDIF'):
                            if ($vfStack->size() == 0) {
                                throw new \Exception('Unbalanced conditional');
                            }
                            // vfExecStack->popBack()
                            // todo
                            break;

                        case $opcodes->getOpByName('OP_VERIFY'):
                            if ($mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation');
                            }
                            $value = $this->castToBool($mainStack->top(-1));
                            if ($value) {
                                $mainStack->pop();
                            } else {
                                throw new \Exception('Error: verify');
                            }

                            break;

                        case $opcodes->getOpByName('OP_RESERVED'):
                            // todo
                            break;

                        case $opcodes->getOpByName('OP_RETURN'):
                            throw new \Exception('Error: OP_RETURN');
                        case $opcodes->getOpByName('OP_TOALTSTACK'):
                            if ($mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation OP_TOALTSTACK');
                            }
                            $altStack->push($mainStack->pop());
                            break;

                        case $opcodes->getOpByName('OP_FROMALTSTACK'):
                            if ($altStack->size() < 1) {
                                throw new \Exception('Invalid alt-stack operation OP_FROMALTSTACK');
                            }
                            $mainStack->push($altStack->pop());
                            break;

                        case $opcodes->getOpByName('OP_2DROP'):
                            if ($mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_2DROP');
                            }
                            $mainStack->pop();
                            $mainStack->pop();
                            break;

                        case $opcodes->getOpByName('OP_2DUP'):
                            if ($mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_2DUP');
                            }
                            $string1 = $mainStack->top(-2);
                            $string2 = $mainStack->top(-1);
                            $mainStack->push($string1);
                            $mainStack->push($string2);
                            break;

                        case $opcodes->getOpByName('OP_3DUP'):
                            if ($mainStack->size() < 3) {
                                throw new \Exception('Invalid stack operation OP_3DUP');
                            }
                            $string1 = $mainStack->top(-3);
                            $string2 = $mainStack->top(-2);
                            $string3 = $mainStack->top(-1);
                            $mainStack->push($string1);
                            $mainStack->push($string2);
                            $mainStack->push($string3);
                            break;

                        case $opcodes->getOpByName('OP_2OVER'):
                            if ($mainStack->size() < 4) {
                                throw new \Exception('Invalid stack operation OP_2OVER');
                            }
                            $string1 = $mainStack->top(-4);
                            $string2 = $mainStack->top(-3);
                            $mainStack->push($string1);
                            $mainStack->push($string2);
                            break;

                        case $opcodes->getOpByName('OP_2ROT'):
                            if ($mainStack->size() < 6) {
                                throw new \Exception('Invalid stack operation OP_2ROT');
                            }
                            $string1 = $mainStack->top(-6);
                            $string2 = $mainStack->top(-5);
                            $mainStack->erase(-6);
                            $mainStack->erase(-5);
                            $mainStack->push($string1);
                            $mainStack->push($string2);
                            break;

                        case $opcodes->getOpByName('OP_2SWAP'):
                            if ($mainStack->size() < 4) {
                                throw new \Exception('Invalid stack operation OP_2SWAP');
                            }
                            $mainStack->swap(-3, -1);
                            $mainStack->swap(-4, -2);
                            break;

                        case $opcodes->getOpByName('OP_IFDUP'):
                            // If top value not zero, duplicate it.
                            if ($mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation OP_IFDUP');
                            }
                            $vch = $mainStack->top(-1);
                            if ($this->castToBool($vch)) {
                                $mainStack->push($vch);
                            }
                            break;

                        case $opcodes->getOpByName('OP_DEPTH'):
                            $num = $mainStack->size();
                            $bin = Buffer::hex($math->decHex($num));
                            $mainStack->push($bin);
                            break;

                        case $opcodes->getOpByName('OP_DROP'):
                            if ($mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation OP_DROP');
                            }
                            $mainStack->pop();
                            break;

                        case $opcodes->getOpByName('OP_DUP'):
                            if ($mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation OP_DUP');
                            }
                            $vch = $mainStack->top(-1);
                            $mainStack->push($vch);
                            break;

                        case $opcodes->getOpByName('OP_NIP'):
                            if ($mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_NIP');
                            }
                            $mainStack->erase(-2);
                            break;

                        case $opcodes->getOpByName('OP_OVER'):
                            if ($mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_OVER');
                            }
                            $vch = $mainStack->top(-2);
                            $mainStack->push($vch);
                            break;

                        case $opcodes->getOpByName('OP_PICK'):
                        case $opcodes->getOpByName('OP_ROLL'):
                            if ($mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_PICK');
                            }
                            $n = $mainStack->top(-1)->getInt();
                            $mainStack->pop();
                            if ($math->cmp($n, 0) < 0 || $math->cmp($n, $mainStack->size()) >= 0) {
                                throw new \Exception('Invalid stack operation OP_PICK');
                            }

                            $pos = $math->sub($math->sub(0, $n), 1);
                            $vch = $mainStack->top($pos);
                            if ($opcodes->isOp($opCode, 'OP_ROLL')) {
                                $mainStack->erase($pos);
                            }
                            $mainStack->push($vch);
                            break;

                        case $opcodes->getOpByName('OP_ROT'):
                            if ($mainStack->size() < 3) {
                                throw new \Exception('Invalid stack operation OP_ROT');
                            }
                            $mainStack->swap(-3, -2);
                            $mainStack->swap(-2, -1);
                            break;

                        case $opcodes->getOpByName('OP_SWAP'):
                            if ($mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_SWAP');
                            }
                            $mainStack->swap(-2, -1);
                            break;

                        case $opcodes->getOpByName('OP_TUCK'):
                            if ($mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_TUCK');
                            }
                            $vch = $mainStack->top(-1);
                            $mainStack->insert($mainStack->end() - 2, $vch);
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

                        case $opcodes->getOpByName('OP_1ADD'):
                        case $opcodes->getOpByName('OP_1SUB'):
                        case $opcodes->getOpByName('OP_2MUL'):
                        case $opcodes->getOpByName('OP_NEGATE'):
                        case $opcodes->getOpByName('OP_ABS'):
                        case $opcodes->getOpByName('OP_NOT'):
                        case $opcodes->getOpByName('OP_0NOTEQUAL'):
                            if ($mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation 1ADD');
                            }
                            $num = $mainStack->top(-1)->getInt();

                            switch ($opCode) {
                                case $opcodes->getOpByName('OP_1ADD'):
                                    $num = $math->add($num, '1');
                                    break;
                                case $opcodes->getOpByName('OP_1SUB'):
                                    $num = $math->sub($num, '1');
                                    break;
                                case $opcodes->getOpByName('OP_2MUL'):
                                    $num = $math->mul(2, $num);
                                    break;
                                case $opcodes->getOpByName('OP_NEGATE'):
                                    $num = $math->sub(0, $num);
                                    break;
                                case $opcodes->getOpByName('OP_ABS'):
                                    if ($math->cmp($num, '0') < 0) {
                                        $num = $math->sub(0, $num);
                                    }
                                    break;
                                case $opcodes->getOpByName('OP_NOT'):
                                    $num = ($math->cmp($num, '0') == 0);
                                    break;
                                case $opcodes->getOpByName('OP_0NOTEQUAL'):
                                    $num = ($math->cmp($num, '0') !== 0);
                                    break;
                                default:
                                    throw new \Exception('Opcode not found');
                            }

                            $mainStack->pop();

                            $buffer = Buffer::hex($math->decHex($num));
                            $mainStack->push($buffer);
                            break;

                        case $opcodes->getOpByName('OP_ADD'):
                        case $opcodes->getOpByName('OP_SUB'):
                        case $opcodes->getOpByName('OP_BOOLAND'):
                        case $opcodes->getOpByName('OP_BOOLOR'):
                        case $opcodes->getOpByName('OP_NUMEQUAL'):
                        case $opcodes->getOpByName('OP_NUMEQUALVERIFY'):
                        case $opcodes->getOpByName('OP_NUMNOTEQUAL'):
                        case $opcodes->getOpByName('OP_LESSTHAN'):
                        case $opcodes->getOpByName('OP_GREATERTHAN'):
                        case $opcodes->getOpByName('OP_LESSTHANOREQUAL'):
                        case $opcodes->getOpByName('OP_GREATERTHANOREQUAL'):
                        case $opcodes->getOpByName('OP_MIN'):
                        case $opcodes->getOpByName('OP_MAX'):
                            if ($mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation (greater than)');
                            }
                            $num1 = $mainStack->top(-2)->getInt();
                            $num2 = $mainStack->top(-1)->getInt();

                            switch ($opCode) {
                                case $opcodes->getOpByName('OP_ADD'):
                                    $num = $math->add($num1, $num2);
                                    break;
                                case $opcodes->getOpByName('OP_SUB'):
                                    $num = $math->sub($num1, $num2);
                                    break;
                                case $opcodes->getOpByName('OP_BOOLAND'):
                                    $num = ($math->cmp($num1, $_bn0->getInt()) !== 0 && $math->cmp($num2, $_bn0->getInt()) !== 0);
                                    break;
                                case $opcodes->getOpByName('OP_BOOLOR'):
                                    $num = ($math->cmp($num1, $_bn0->getInt()) !== 0 || $math->cmp($num2, $_bn0->getInt()) !== 0);
                                    break;
                                case $opcodes->getOpByName('OP_NUMEQUAL'):
                                    $num = ($math->cmp($num1, $num2) == 0);
                                    break;
                                case $opcodes->getOpByName('OP_NUMEQUALVERIFY'):
                                    $num = ($math->cmp($num1, $num2) == 0);
                                    break;
                                case $opcodes->getOpByName('OP_NUMNOTEQUAL'):
                                    $num = ($math->cmp($num1, $num2) !== 0);
                                    break;
                                case $opcodes->getOpByName('OP_LESSTHAN'):
                                    $num = ($math->cmp($num1, $num2) < 0);
                                    break;
                                case $opcodes->getOpByName('OP_GREATERTHAN'):
                                    $num = ($math->cmp($num1, $num2) > 0);
                                    break;
                                case $opcodes->getOpByName('OP_LESSTHANOREQUAL'):
                                    $num = ($math->cmp($num1, $num2) <= 0);
                                    break;
                                case $opcodes->getOpByName('OP_GREATERTHANOREQUAL'):
                                    $num = ($math->cmp($num1, $num2) >= 0);
                                    break;
                                case $opcodes->getOpByName('OP_MIN'):
                                    $num = ($math->cmp($num1, $num2) <= 0) ? $num1 : $num2;
                                    break;
                                case $opcodes->getOpByName('OP_MAX'):
                                    $num = ($math->cmp($num1, $num2) >= 0) ? $num1 : $num2;
                                    break;
                                default:
                                    throw new \Exception('Opcode not found');
                            }


                            $mainStack->pop();
                            $mainStack->pop();
                            $buffer = Buffer::hex($math->decHex($num));
                            $mainStack->push($buffer);
                            if ($opcodes->isOp($opCode, 'OP_NUMEQUALVERIFY')) {
                                if ($this->castToBool($mainStack->top(-1))) {
                                    $mainStack->pop();
                                } else {
                                    throw new \Exception('NUM EQUAL VERIFY error');
                                }
                            }
                            break;

                        case $opcodes->getOpByName('OP_WITHIN'):
                            if ($mainStack->size() < 3) {
                                throw new \Exception('Invalid stack operation');
                            }
                            $num1 = $mainStack->top(-3)->getInt();
                            $num2 = $mainStack->top(-2)->getInt();
                            $num3 = $mainStack->top(-1)->getInt();

                            $value = ($math->cmp($num2, $num1) <= 0 && $math->cmp($num1, $num3) < 0);
                            $mainStack->pop();
                            $mainStack->pop();
                            $mainStack->pop();
                            $mainStack->push($value ? $_bn1 : $_bn0);
                            break;

                        case $opcodes->getOpByName('OP_RIPEMD160'):
                        case $opcodes->getOpByName('OP_SHA1'):
                        case $opcodes->getOpByName('OP_SHA256'):
                        case $opcodes->getOpByName('OP_HASH160'):
                        case $opcodes->getOpByName('OP_HASH256'):
                            if ($mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation');
                            }
                            $vch = $mainStack->top(-1);
                            if ($opcodes->isOp($opCode, 'OP_RIPEMD160')) {
                                $hash = Hash::ripemd160($vch);
                            } elseif ($opcodes->isOp($opCode, 'OP_SHA1')) {
                                $hash = Hash::sha1($vch);
                            } elseif ($opcodes->isOp($opCode, 'OP_SHA256')) {
                                $hash = Hash::sha256($vch);
                            } elseif ($opcodes->isOp($opCode, 'OP_HASH160')) {
                                $hash = Hash::sha256ripe160($vch);
                            } elseif ($opcodes->isOp($opCode, 'OP_HASH256')) {
                                $hash = Hash::sha256d($vch);
                            } else {
                                throw new \Exception('Opcode not found');
                            }

                            $mainStack->pop();
                            $mainStack->push($hash);
                            break;

                        case $opcodes->getOpByName('OP_CODESEPARATOR'):
                            if ($mainStack->size() < 4) {
                                throw new \Exception('Invalid stack operation');
                            }
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

                            $script = ScriptFactory::create();
                            $sigHash = $this->transaction
                                ->getSignatureHash()
                                ->calculate($script, $this->inputToSign, $txSig->getHashType());

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

                        default:
                            throw new \Exception('Opcode not found');
                    }

                }
            }

            return true;
        } catch (ScriptRuntimeException $e) {
            // Failure due to script tags, can access flag: $e->getFailureFlag()
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
