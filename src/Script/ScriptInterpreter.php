<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Signature\TransactionSignatureFactory;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Key\PublicKey;
use BitWasp\Bitcoin\Exceptions\ScriptStackException;
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
     * @var ScriptStack
     */
    public $mainStack;

    /**
     * @var ScriptStack
     */
    protected $altStack;

    /**
     * @var ScriptStack
     */
    protected $vfExecStack;

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

        $this->mainStack = ScriptFactory::stack();
        $this->altStack = ScriptFactory::stack();
        $this->vfExecStack = ScriptFactory::stack();

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
     * @param $opCodeStr
     * @return bool
     */
    public function isDisabledOpByName($opCodeStr)
    {
        return in_array($opCodeStr, $this->getDisabledOpcodes());
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
    public function castToBool($value)
    {
        if ($value instanceof Buffer) {
            // Since we're using buffers, lets try ensuring the contents are not 0.
            return ($this->ecAdapter->getMath()->cmp($value->getInt(), 0) !== 0);
        }

        if ($value) {
            return true;
        }

        return false;
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
     * @param Buffer $signature
     * @return bool
     * @throws \BitWasp\Bitcoin\Exceptions\SignatureNotCanonical
     */
    public function checkSignatureEncoding(Buffer $signature)
    {
        if ($signature->getSize() == 0) {
            return true;
        }

        $result = true;
        if ($this->flags->verifyDERSignatures) {
            $result &= TransactionSignature::isDERSignature($signature);
        }

        return $result;
    }

    /**
     * @param Buffer $publicKey
     * @return bool
     * @throws \Exception
     */
    public function checkPublicKeyEncoding(Buffer $publicKey)
    {
        if ($this->flags->verifyStrictEncoding && !PublicKey::isCompressedOrUncompressed($publicKey)) {
            throw new \Exception('Invalid public key type');
        }

        return true;
    }

    /**
     * @param ScriptInterface $script
     * @return $this
     */
    public function setScript(ScriptInterface $script = null)
    {
        if ($script == null) {
            $script = ScriptFactory::create();
        }
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

        $stackCopy = ScriptFactory::stack();
        if ($this->flags->verifyP2SH) {
            $stackCopy = $this->mainStack;
        }

        if (!$this->setScript($scriptSig)->run()) {
            return false;
        }

        if ($this->mainStack->size() == 0) {
            throw new \Exception('Script err eval false');
        }

        if ($this->castToBool($this->mainStack->top(-1)) === false) {
            throw new \Exception('Script err eval false literally');
        }

        $verifier = new OutputClassifier($scriptPubKey);

        if ($this->flags->verifyP2SH && $verifier->isPayToScriptHash()) {
            if (!$scriptSig->isPushOnly()) { // todo
                throw new \Exception('P2SH script must be push only');
            }

            $this->mainStack = $stackCopy;

            if ($this->mainStack->size() == 0) {
                throw new \Exception('Script err eval false');
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
        $this->opCount = 0;
        $parser = $this->script->getScriptParser();

        $checkFExec = function () {
            $c = 0;
            for ($i = 0, $len = $this->vfExecStack->end(); $i < $len; $i++) {
                if ($this->vfExecStack->top(0 - $len - $i) == true) {
                    $c++;
                }
            }
            return (bool)$c;
        };

        try {
            while ($parser->next($opCode, $pushData) === true) {
                $fExec = !$checkFExec();

                // If pushdata was written to,
                if ($pushData instanceof Buffer && $pushData->getSize() > $this->flags->maxElementSize) {
                    throw new \Exception('Error - push size');
                }

                // OP_RESERVED should not count towards opCount
                if ($this->script->getOpcodes()->cmp($opCode, 'OP_16') > 0 && ++$this->opCount > 201) {
                    throw new \Exception('Error - Script Op Count');
                }

                if ($this->flags->checkDisabledOpcodes) {
                    if ($this->isDisabledOp($opCode)) {
                        throw new \Exception('Disabled Opcode');
                    }
                }

                if ($fExec && $opCode >= 0 && $opcodes->cmp($opCode, 'OP_PUSHDATA4') <= 0) {
                    // In range of a pushdata opcode
                    if ($this->flags->verifyMinimalPushdata && !$this->checkMinimalPush($opCode, $pushData)) {
                        throw new \Exception('Minimal pushdata required');
                    }
                    $this->mainStack->push($pushData);

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
                            $this->mainStack->push($num);
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
                            if ($this->flags->discourageUpgradableNOPS) {
                                throw new \Exception('Upgradable NOPS found - this is discouraged');
                            }
                            break;

                        case $opcodes->getOpByName('OP_IF'):
                        case $opcodes->getOpByName('OP_NOTIF'):
                            // <expression> if [statements] [else [statements]] endif
                            $value = false;
                            if ($fExec) {
                                if ($this->mainStack->size() < 1) {
                                    throw new \Exception('Unbalanced conditional');
                                }
                                // todo
                                $buffer = $this->mainStack->pop(-1);
                                $value = $this->castToBool($buffer);
                                if ($opcodes->isOp($opCode, 'OP_NOTIF')) {
                                    $value = !$value;
                                }
                            }
                            $this->vfExecStack->push($value);
                            break;

                        case $opcodes->getOpByName('OP_ELSE'):
                            if ($this->vfExecStack->size() == 0) {
                                throw new \Exception('Unbalanced conditional');
                            }
                            $this->vfExecStack->set($this->vfExecStack->end() - 1, !$this->vfExecStack->end());
                            break;

                        case $opcodes->getOpByName('OP_ENDIF'):
                            if ($this->vfExecStack->size() == 0) {
                                throw new \Exception('Unbalanced conditional');
                            }
                            // vfExecStack->popBack()
                            // todo
                            break;

                        case $opcodes->getOpByName('OP_VERIFY'):
                            if ($this->mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation');
                            }
                            $value = $this->castToBool($this->mainStack->top(-1));
                            if ($value) {
                                $this->mainStack->pop();
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
                            if ($this->mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation OP_TOALTSTACK');
                            }
                            $this->altStack->push($this->mainStack->pop());
                            //$this->altStack->push($this->mainStack->top(-1));
                            //$this->mainStack->pop();
                            break;

                        case $opcodes->getOpByName('OP_FROMALTSTACK'):
                            if ($this->altStack->size() < 1) {
                                throw new \Exception('Invalid alt-stack operation OP_FROMALTSTACK');
                            }
                            $this->mainStack->push($this->altStack->pop());
                            break;

                        case $opcodes->getOpByName('OP_2DROP'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_2DROP');
                            }
                            $this->mainStack->pop();
                            $this->mainStack->pop();
                            break;

                        case $opcodes->getOpByName('OP_2DUP'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_2DUP');
                            }
                            $string1 = $this->mainStack->top(-2);
                            $string2 = $this->mainStack->top(-1);
                            $this->mainStack->push($string1);
                            $this->mainStack->push($string2);
                            break;

                        case $opcodes->getOpByName('OP_3DUP'):
                            if ($this->mainStack->size() < 3) {
                                throw new \Exception('Invalid stack operation OP_3DUP');
                            }
                            $string1 = $this->mainStack->top(-3);
                            $string2 = $this->mainStack->top(-2);
                            $string3 = $this->mainStack->top(-1);
                            $this->mainStack->push($string1);
                            $this->mainStack->push($string2);
                            $this->mainStack->push($string3);
                            break;

                        case $opcodes->getOpByName('OP_2OVER'):
                            if ($this->mainStack->size() < 4) {
                                throw new \Exception('Invalid stack operation OP_2OVER');
                            }
                            $string1 = $this->mainStack->top(-4);
                            $string2 = $this->mainStack->top(-3);
                            $this->mainStack->push($string1);
                            $this->mainStack->push($string2);
                            break;

                        case $opcodes->getOpByName('OP_2ROT'):
                            if ($this->mainStack->size() < 6) {
                                throw new \Exception('Invalid stack operation OP_2ROT');
                            }
                            $string1 = $this->mainStack->top(-6);
                            $string2 = $this->mainStack->top(-5);
                            $this->mainStack->erase($this->mainStack->size() - 6);
                            $this->mainStack->erase($this->mainStack->size() - 4);
                            $this->mainStack->push($string1);
                            $this->mainStack->push($string2);
                            break;

                        case $opcodes->getOpByName('OP_2SWAP'):
                            if ($this->mainStack->size() < 4) {
                                throw new \Exception('Invalid stack operation OP_2SWAP');
                            }
                            $this->mainStack->swap(-3, -1);
                            $this->mainStack->swap(-4, -2);

                            break;

                        case $opcodes->getOpByName('OP_IFDUP'):
                            if ($this->mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation OP_IFDUP');
                            }
                            $vch = $this->mainStack->top(-1);
                            if ($this->castToBool($vch)) {
                                $this->mainStack->push($vch);
                            }
                            break;

                        case $opcodes->getOpByName('OP_DEPTH'):
                            $num = $this->mainStack->size();
                            $bin = Buffer::hex($math->decHex($num));
                            $this->mainStack->push($bin);
                            break;

                        case $opcodes->getOpByName('OP_DROP'):
                            if ($this->mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation OP_DROP');
                            }
                            $this->mainStack->pop();
                            break;

                        case $opcodes->getOpByName('OP_DUP'):
                            if ($this->mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation OP_DUP');
                            }
                            $vch = $this->mainStack->top(-1);
                            $this->mainStack->push($vch);
                            break;

                        case $opcodes->getOpByName('OP_NIP'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_NIP');
                            }
                            $this->mainStack->erase(-2);
                            break;

                        case $opcodes->getOpByName('OP_OVER'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_OVER');
                            }
                            $vch = $this->mainStack->top(-2);
                            $this->mainStack->push($vch);
                            break;

                        case $opcodes->getOpByName('OP_PICK'):
                        case $opcodes->getOpByName('OP_ROLL'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operationOP_PICK');
                            }
                            $n = $this->mainStack->top(-1)->getInt();
                            $this->mainStack->pop();
                            echo " $n < 0?  or:  $n >= " . $this->mainStack->size() . "\n";
                            if ($math->cmp($n, 0) < 0 || $math->cmp($n, $this->mainStack->size()) >= 0) {
                                throw new \Exception('Invalid stack operation OP_PICK');
                            }

                            $pos = $math->sub($math->sub(0, $n), 1);//$math->sub($n, 1));
                            $vch = $this->mainStack->top($pos);
                            if ($opcodes->isOp($opCode, 'OP_ROLL')) {
                                $this->mainStack->erase($pos);
                            }
                            $this->mainStack->push($vch);
                            break;

                        case $opcodes->getOpByName('OP_ROT'):
                            if ($this->mainStack->size() < 3) {
                                throw new \Exception('Invalid stack operation OP_ROT');
                            }
                            $this->mainStack->swap(-3, -2);
                            $this->mainStack->swap(-2, -1);
                            break;

                        case $opcodes->getOpByName('OP_SWAP'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_SWAP');
                            }
                            $this->mainStack->swap(-2, -1);
                            break;

                        case $opcodes->getOpByName('OP_TUCK'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_TUCK');
                            }
                            $vch = $this->mainStack->top(-1);
                            $this->mainStack->insert($this->mainStack->end() - 2, $vch);
                            break;

                        case $opcodes->getOpByName('OP_SIZE'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_SIZE');
                            }
                            // todo
                            // Different types could be returned here

                            $vch = $this->mainStack->top(-1);
                            $size = pack("H*", $math->decHex(strlen($vch)));

                            $this->mainStack->push($size);
                            break;

                        case $opcodes->getOpByName('OP_EQUAL'):
                        case $opcodes->getOpByName('OP_EQUALVERIFY'):
                        //case $this->isOp($opCode, 'OP_NOTEQUAL'): // use OP_NUMNOTEQUAL
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_EQUAL');
                            }
                            $vch1 = $this->mainStack->top(-2);
                            $vch2 = $this->mainStack->top(-1);

                            echo " (".$vch1->getHex() . " === " . $vch2->getHex() . ") \n";
                            $equal = ($vch1->getBinary() === $vch2->getBinary());

                            // OP_NOTEQUAL is disabled
                            //if ($this->isOp($opCode, 'OP_NOTEQUAL')) {
                            //    $equal = !$equal;
                            //}

                            $this->mainStack->pop();
                            $this->mainStack->pop();
                            $this->mainStack->push(($equal ? true : false));

                            if ($opcodes->isOp($opCode, 'OP_EQUALVERIFY')) {
                                if ($equal) {
                                    $this->mainStack->pop();
                                } else {
                                    throw new \Exception('Error EQUALVERIFY');
                                }
                            }
                            break;

                        case $opcodes->getOpByName('OP_1ADD'):
                        case $opcodes->getOpByName('OP_1SUB'):
                        case $opcodes->getOpByName('OP_NEGATE'):
                        case $opcodes->getOpByName('OP_ABS'):
                        case $opcodes->getOpByName('OP_NOT'):
                        case $opcodes->getOpByName('OP_0NOTEQUAL'):
                            if ($this->mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation 1ADD');
                            }
                            $num = $this->mainStack->top(-1);

                            switch ($opCode) {
                                case $opcodes->getOpByName('OP_1ADD'):
                                    $num = $math->add($num->getInt(), '1');
                                    break;
                                case $opcodes->getOpByName('OP_1SUB'):
                                    $num = $math->sub($num, '1');
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
                                    throw new \Exception('Invalid Opcode');
                            }

                            $this->mainStack->pop();
                            $this->mainStack->push($num);
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
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation (greater than)');
                            }
                            $num1 = $this->mainStack->top(-2);
                            $num2 = $this->mainStack->top(-1);
                            $_bn0 = '0';

                            switch ($opCode) {
                                case $opcodes->getOpByName('OP_ADD'):
                                    $num = $math->add($num1->getInt(), $num2->getInt());
                                    break;
                                case $opcodes->getOpByName('OP_SUB'):
                                    $num = $math->sub($num1, $num2);
                                    break;
                                case $opcodes->getOpByName('OP_BOOLAND'):
                                    $num = ($math->cmp($num1, $_bn0) !== 0 && $math->cmp($num2, $_bn0) !== 0);
                                    break;
                                case $opcodes->getOpByName('OP_BOOLOR'):
                                    $num = ($math->cmp($num1, $_bn0) !== 0 || $math->cmp($num2, $_bn0) !== 0);
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
                                    throw new \Exception('Invalid opcode in maths ops');
                            }
                            $this->mainStack->pop();
                            $this->mainStack->pop();
                            $this->mainStack->push($num);
                            if ($opcodes->isOp($opCode, 'OP_NUMEQUALVERIFY')) {
                                if ($this->castToBool($this->mainStack->top(-1))) {
                                    $this->mainStack->pop();
                                } else {
                                    throw new \Exception('NUM EQUAL VERIFY error');
                                }
                            }
                            break;

                        case $opcodes->getOpByName('OP_WITHIN'):
                            if ($this->mainStack->size() < 3) {
                                throw new \Exception('Invalid stack operation');
                            }
                            $num1 = $this->mainStack->top(-3);
                            $num2 = $this->mainStack->top(-2);
                            $num3 = $this->mainStack->top(-1);
                            $value = ($math->cmp($num2, $num1) <= 0 && $math->cmp($num1, $num3) < 0);
                            $this->mainStack->pop();
                            $this->mainStack->pop();
                            $this->mainStack->pop();
                            $this->mainStack->push($value ? true : false);
                            break;

                        case $opcodes->getOpByName('OP_RIPEMD160'):
                        case $opcodes->getOpByName('OP_SHA1'):
                        case $opcodes->getOpByName('OP_SHA256'):
                        case $opcodes->getOpByName('OP_HASH160'):
                        case $opcodes->getOpByName('OP_HASH256'):
                            if ($this->mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation');
                            }
                            $vch = $this->mainStack->top(-1);
                            $hashLen = (
                                $opcodes->isOp($opCode, 'OP_RIPEMD160')
                                || $opcodes->isOp($opCode, 'OP_SHA1')
                                || $opcodes->isOp($opCode, 'OP_HASH160')
                            ) ? 20 : 32;

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
                            }

                            $this->mainStack->pop();
                            $this->mainStack->push($hash);
                            break;

                        case $opcodes->getOpByName('OP_CODESEPARATOR'):
                            if ($this->mainStack->size() < 4) {
                                throw new \Exception('Invalid stack operation');
                            }
                            $this->hashStartPos = $parser->getPosition();
                            break;

                        case $opcodes->getOpByName('OP_CHECKSIG'):
                        case $opcodes->getOpByName('OP_CHECKSIGVERIFY'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation');
                            }

                            $vchPubKey = $this->mainStack->top(-1);
                            $vchSig = $this->mainStack->top(-2);

                            if (!$this->checkSignatureEncoding($vchSig) || !$this->checkPublicKeyEncoding($vchPubKey)) {
                                return false;
                            }

                            $txSig = TransactionSignatureFactory::fromHex($vchSig);
                            $publicKey = PublicKeyFactory::fromHex($vchPubKey);

                            $script = ScriptFactory::create();
                            $sigHash = $this->transaction
                                ->getSignatureHash()
                                ->calculate($script, $this->inputToSign, $txSig->getHashType());
                            $success = $this->ecAdapter->verify($sigHash, $publicKey, $txSig->getSignature());

                            $this->mainStack->pop();
                            $this->mainStack->pop();
                            $this->mainStack->push($success ? $this->constTrue : $this->constFalse);

                            if ($opcodes->isOp($opCode, 'OP_CHECKSIGVERIFY')) {
                                if ($success) {
                                    $this->mainStack->pop();
                                } else {
                                    throw new \Exception('Checksig verify');
                                }
                            }

                            break;
                    }
                }
            }

            return true;
        } catch (ScriptRuntimeException $e) {
            echo "$$ SCRIPT RUNTIEM ERROR $$\n";
            return false;

        } catch (ScriptStackException $e) {
            echo "$$ SCRIPT STACK ERROR $$\n";
            return false;

        } catch (\Exception $e) {
            echo "Exception\n";
            echo " - " . $e->getMessage() . "\n";
            return false;
        }
    }
}
