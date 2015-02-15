<?php

namespace Afk11\Bitcoin\Script;

use Afk11\Bitcoin\Crypto\Hash;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Script\Classifier\OutputClassifier;
use Afk11\Bitcoin\Transaction\Transaction;
use Afk11\Bitcoin\Key\PublicKey;
use Afk11\Bitcoin\Signature\Signature;
use Afk11\Bitcoin\Exceptions\ScriptStackException;
use Afk11\Bitcoin\Exceptions\ScriptRuntimeException;
use Afk11\Bitcoin\Signature\Signer;
use Afk11\Bitcoin\Script\ScriptInterpreterFlags;
use Mdanter\Ecc\GeneratorPoint;

class ScriptInterpreter implements ScriptInterpreterInterface
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var GeneratorPoint
     */
    private $generator;

    /**
     * @var int|string
     */
    private $inputToSign;

    /**
     * @var Script
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
     * @param Math $math
     * @param GeneratorPoint $generator
     * @param Transaction $transaction
     */
    public function __construct(Math $math, GeneratorPoint $generator, Transaction $transaction, ScriptInterpreterFlags $flags = null)
    {
        $this->math                     = $math;
        $this->generator                = $generator;
        $this->transaction              = $transaction;
        $this->script                   = new Script();
        $this->flags                    = $flags ?: ScriptInterpreterFlags::defaults();

        $this->mainStack                = new ScriptStack;
        $this->altStack                 = new ScriptStack;
        $this->vfExecStack              = new ScriptStack;

        $this->constTrue                = pack("H*", '01');
        $this->constFalse               = pack("H*", '00');

        return $this;
    }

    /**
     * @return array
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
                return $this->script->getOpCode($value);
            },
            $this->getDisabledOpcodes()
        );
    }

    /**
     * @param $opCodeStr
     * @return bool
     */
    public function isDisabledOpCode($opCodeStr)
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
     * @param $op
     * @param $opCodeStr
     * @return int
     * @throws \Exception
     */
    public function compareOp($op, $opCodeStr)
    {
        try {
            $match = $this->math->cmp($op, $this->script->getOpCode($opCodeStr));
        } catch (\Exception $e) {
            $match = false;
        }

        return $match;
    }

    /**
     * @param $op
     * @param $opCodeStr
     * @return bool
     */
    public function isOp($op, $opCodeStr)
    {
        return (bool)$this->compareOp($op, $opCodeStr) == 0;
    }

    /**
     * Cast the value to a boolean
     *
     * @param $value
     * @return bool
     */
    public function castToBool($value)
    {
        if ($value) {
            return true;
        }

        return false;
    }

    /**
     * @param $opCode
     * @param string $pushData
     * @return bool
     * @throws \Exception
     */
    public function checkMinimalPush($opCode, $pushData)
    {
        $pushSize = strlen($pushData);

        if ($pushSize == 0) {
            return $this->compareOp($opCode, 'OP_0') == 0;
        } elseif ($pushSize == 1 && ord($pushData[0]) >= 1 && $pushData[0] <= 16) {
            return $opCode == $this->script->getOpCode('OP_1') + ( ord($pushData[0]) - 1);
        } elseif ($pushSize == 1 && ord($pushData) == 0x81) {
            return $this->compareOp($opCode, 'OP_1NEGATE') == 0;
        } elseif ($pushSize <= 75) {
            return $opCode == $pushSize;
        } elseif ($pushSize <= 255) {
            return $this->compareOp($opCode, 'OP_PUSHDATA1') == 0;
        } elseif ($pushSize <= 65535) {
            return $this->compareOp($opCode, 'OP_PUSHDATA2') == 0;
        }

        return true;
    }

    /**
     * @param $script
     * @param $position
     * @param $posEnd
     * @param $opCode
     * @param string $pushData
     * @return bool
     * @throws \Exception
     */
    public function getOp(&$script, &$position, $posEnd, &$opCode, &$pushData = null)
    {
        $opCode = $this->script->getOpCode('OP_INVALIDOPCODE');

        if ($this->math->cmp($position, $posEnd) >= 0) {
            return false;
        }

        $opCode = ord($script[$position++]);

        if ($this->compareOp($opCode, 'OP_PUSHDATA4') <= 0) {
            // opCode < OP_PUSHDATA1 - then just take opCode as the length, do not seek more
            if ($this->compareOp($opCode, 'OP_PUSHDATA1') < 0) {
                $size = $opCode;

            } else if ($this->isOp($opCode, 'OP_PUSHDATA1')) {
                if ($posEnd - $position < 1) {
                    return false;
                }

                $size = ord($script[$position]);
                $position++;

            } else if ($this->isOp($opCode, 'OP_PUSHDATA2')) {
                if (($posEnd - $position) < 2) {
                    return false;
                }

                $size = unpack("v", substr($script, $position, 2));
                $size = $size[1];
                $position += 2;

            } else if ($this->isOp($opCode, 'OP_PUSHDATA4')) {
                if ($posEnd - $position < 4) {
                    return false;
                }
                $size = unpack("V", substr($script, $position, 4));
                $size = $size[1];

                $position += 4;
            }

            // Position should now be at the start of the string
            if (($posEnd - $position) < 0 || ($posEnd - $position) < $size) {
                return false;
            }

            $pushData = substr($script, $position, $size);
            $position += $size;

        }

        return true;
    }

    /**
     * @param Buffer $signature
     * @return bool
     * @throws \Afk11\Bitcoin\Exceptions\SignatureNotCanonical
     */
    public function checkSignatureEncoding(Buffer $signature)
    {
        if ($signature->getSize() == 0) {
            return true;
        }

        $result = true;
        if ($this->flags->verifyDERSignatures) {
            $result &= Signature::isDERSignature($signature);
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
     * @param Script $script
     * @return $this
     */
    public function setScript(Script $script = null)
    {
        if ($script == null) {
            $script = new Script();
        }

        $this->script = $script;
        return $this;
    }

    /**
     * @param Script $scriptSig
     * @param Script $scriptPubKey
     * @param $nInputToSign
     * @return bool
     * @throws \Exception
     */
    public function verify(Script $scriptSig, Script $scriptPubKey, $nInputToSign)
    {
        $this->inputToSign = $nInputToSign;
        if (!$this->setScript($scriptSig)->run()) {
            return false;
        }

        $stackCopy = new ScriptStack;
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
            if (!$scriptSig->isPushOnly()) {
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

        $script        = $this->script->serialize();
        $posScriptEnd  = strlen($script);
        $pos           = 0;
        $this->opCount = 0;
        $opCode        = null;
        $checkFExec = function () {
            $c = 0;
            for ($i = 0, $len = $this->vfExecStack->end(); $i < $len; $i++) {
                if ($this->vfExecStack->top(0-$len-$i) == true) {
                    $c++;
                }
            }
            return (bool)$c;
        };

        try {
            while ($pos < $posScriptEnd) {
                $pushData = '';
                $fExec = !$checkFExec();

                if (!$this->getOp($script, $pos, $posScriptEnd, $opCode, $pushData)) {
                    throw new \Exception("Bad opcode: $opCode");
                }

                if (strlen($pushData) > $this->flags->maxElementSize) {
                    throw new \Exception('Error - push size');
                }

                if ($this->compareOp($opCode, 'OP_16') > 0 && ++$this->opCount > 201) {
                    throw new \Exception('Error - Script Op Count');
                }

                if ($this->flags->checkDisabledOpcodes) {
                    if ($this->isDisabledOp($opCode)) {
                        throw new \Exception('Disabled Opcode');
                    }
                }

                if ($fExec && $opCode >= 0 && $this->compareOp($opCode, 'OP_PUSHDATA4') <= 0) {
                    if ($this->flags->verifyMinimalPushdata && !$this->checkMinimalPush($opCode, $pushData)) {
                        throw new \Exception('Minimal pushdata required');
                    }
                    $this->mainStack->push($pushData);

                } elseif ($fExec || ($this->compareOp($opCode, 'OP_IF') <= 0 && $this->compareOp($opCode, 'OP_ENDIF'))) {
                    switch ($opCode)
                    {
                        case $this->isOp($opCode, 'OP_1NEGATE'):
                        case $this->isOp($opCode, 'OP_1'):
                        case $this->isOp($opCode, 'OP_2'):
                        case $this->isOp($opCode, 'OP_3'):
                        case $this->isOp($opCode, 'OP_4'):
                        case $this->isOp($opCode, 'OP_5'):
                        case $this->isOp($opCode, 'OP_6'):
                        case $this->isOp($opCode, 'OP_7'):
                        case $this->isOp($opCode, 'OP_8'):
                        case $this->isOp($opCode, 'OP_9'):
                        case $this->isOp($opCode, 'OP_10'):
                        case $this->isOp($opCode, 'OP_11'):
                        case $this->isOp($opCode, 'OP_12'):
                        case $this->isOp($opCode, 'OP_13'):
                        case $this->isOp($opCode, 'OP_14'):
                        case $this->isOp($opCode, 'OP_15'):
                        case $this->isOp($opCode, 'OP_16'):
                            $num = $opCode - ($this->script->getOpCode('OP_1') - 1);
                            $this->mainStack->push($num);
                            break;

                        case $this->isOp($opCode, 'OP_NOP'):
                            break;

                        case $this->isOp($opCode, 'OP_NOP1'):
                        case $this->isOp($opCode, 'OP_NOP2'):
                        case $this->isOp($opCode, 'OP_NOP3'):
                        case $this->isOp($opCode, 'OP_NOP4'):
                        case $this->isOp($opCode, 'OP_NOP5'):
                        case $this->isOp($opCode, 'OP_NOP6'):
                        case $this->isOp($opCode, 'OP_NOP7'):
                        case $this->isOp($opCode, 'OP_NOP8'):
                        case $this->isOp($opCode, 'OP_NOP9'):
                        case $this->isOp($opCode, 'OP_NOP10'):
                            if ($this->flags->discourageUpgradableNOPS) {
                                throw new \Exception('Upgradable NOPS found - this is discouraged');
                            }
                            break;

                        case $this->isOp($opCode, 'OP_IF'):
                        case $this->isOp($opCode, 'OP_NOTIF'):
                            // <expression> if [statements] [else [statements]] endif
                            $value = false;
                            if ($fExec) {
                                if ($this->mainStack->size() < 1) {
                                    throw new \Exception('Unbalanced conditional');
                                }
                                // todo
                                $string = $this->mainStack->top(-1);
                                $value = $this->castToBool($value);
                                if ($this->isOp($opCode, 'OP_NOTIF')) {
                                    $value = !$value;
                                }
                                $this->mainStack->pop();
                            }
                            $this->vfExecStack->push($value);
                            break;

                        case $this->isOp($opCode, 'OP_ELSE'):
                            if ($this->vfExecStack->size() == 0) {
                                throw new \Exception('Unbalanced conditional');
                            }
                            // $this->vfExecStack->back() = ! $this->vfExecStack->back()
                            // todo
                            break;

                        case $this->isOp($opCode, 'OP_ENDIF'):
                            if ($this->vfExecStack->size() == 0) {
                                throw new \Exception('Unbalanced conditional');
                            }
                            // vfExecStack->popBack()
                            // todo
                            break;

                        case $this->isOp($opCode, 'OP_VERIFY'):
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

                        case $this->isOp($opCode, 'OP_RESERVED'):
                            // todo
                            break;

                        case $this->isOp($opCode, 'OP_RETURN'):
                            throw new \Exception('Error: OP_RETURN');
                            break;

                        case $this->isOp($opCode, 'OP_TOALTSTACK'):
                            if ($this->mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation OP_TOALTSTACK');
                            }
                            $this->altStack->push($this->mainStack->pop());
                            //$this->altStack->push($this->mainStack->top(-1));
                            //$this->mainStack->pop();
                            break;

                        case $this->isOp($opCode, 'OP_FROMALTSTACK'):
                            if ($this->altStack->size() < 1) {
                                throw new \Exception('Invalid alt-stack operation OP_FROMALTSTACK');
                            }
                            $this->mainStack->push($this->altStack->pop());
                            break;

                        case $this->isOp($opCode, 'OP_2DROP'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_2DROP');
                            }
                            $this->mainStack->pop();
                            $this->mainStack->pop();
                            break;

                        case $this->isOp($opCode, 'OP_2DUP'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_2DUP');
                            }
                            $string1 = $this->mainStack->top(-2);
                            $string2 = $this->mainStack->top(-1);
                            $this->mainStack->push($string1);
                            $this->mainStack->push($string2);
                            break;

                        case $this->isOp($opCode, 'OP_3DUP'):
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

                        case $this->isOp($opCode, 'OP_2OVER'):
                            if ($this->mainStack->size() < 4) {
                                throw new \Exception('Invalid stack operation OP_2OVER');
                            }
                            $string1 = $this->mainStack->top(-4);
                            $string2 = $this->mainStack->top(-3);
                            $this->mainStack->push($string1);
                            $this->mainStack->push($string2);
                            break;

                        case $this->isOp($opCode, 'OP_2ROT'):
                            if ($this->mainStack->size() < 6) {
                                throw new \Exception('Invalid stack operation OP_2ROT');
                            }
                            $string1 = $this->mainStack->top(-6);
                            $string2 = $this->mainStack->top(-5);
                            $this->mainStack->erase($this->mainStack->size()-6);
                            $this->mainStack->erase($this->mainStack->size()-4);
                            $this->mainStack->push($string1);
                            $this->mainStack->push($string2);
                            break;

                        case $this->isOp($opCode, 'OP_2SWAP'):
                            if ($this->mainStack->size() < 4) {
                                throw new \Exception('Invalid stack operation OP_2SWAP');
                            }
                            $this->mainStack->swap(-4, -2);
                            $this->mainStack->swap(-3, -1);
                            break;

                        case $this->isOp($opCode, 'OP_IFDUP'):
                            if ($this->mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation OP_IFDUP');
                            }
                            $vch = $this->mainStack->top(-1);
                            if ($this->castToBool($vch)) {
                                $this->mainStack->push($vch);
                            }
                            break;

                        case $this->isOp($opCode, 'OP_DEPTH'):
                            $num = $this->mainStack->size();
                            $bin = pack("H*", $this->math->decHex($num));
                            $this->mainStack->push($bin);
                            break;

                        case $this->isOp($opCode, 'OP_DROP'):
                            if ($this->mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation OP_DROP');
                            }
                            $this->mainStack->pop();
                            break;

                        case $this->isOp($opCode, 'OP_DUP'):
                            if ($this->mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation OP_DUP');
                            }
                            $vch = $this->mainStack->top(-1);
                            $this->mainStack->push($vch);
                            break;

                        case $this->isOp($opCode, 'OP_NIP'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_NIP');
                            }
                            $this->mainStack->erase($this->mainStack->end() - 2);
                            break;

                        case $this->isOp($opCode, 'OP_OVER'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_OVER');
                            }
                            $vch = $this->mainStack->top(-2);
                            $this->mainStack->push($vch);
                            break;

                        case $this->isOp($opCode, 'OP_PICK'):
                        case $this->isOp($opCode, 'OP_ROLL'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operationOP_PICK');
                            }
                            $n = $this->mainStack->top(-1);
                            $this->mainStack->pop();
                            if ($n < 0 || $n >= $this->mainStack->size()) {
                                throw new \Exception('Invalid stack operation OP_PICK');
                            }
                            $vch = $this->mainStack->top(0 - $n - 1);
                            if ($this->isOp($opCode, 'OP_ROLL')) {
                                $this->mainStack->erase($this->mainStack->end() - $n - 1);
                            }
                            $this->mainStack->push($vch);
                            break;

                        case $this->isOp($opCode, 'OP_ROT'):
                            if ($this->mainStack->size() < 3) {
                                throw new \Exception('Invalid stack operation OP_ROT');
                            }
                            $this->mainStack->swap(-3, -2);
                            $this->mainStack->swap(-2, -1);
                            break;

                        case $this->isOp($opCode, 'OP_SWAP'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_SWAP');
                            }
                            $this->mainStack->swap(-2, -1);
                            break;

                        case $this->isOp($opCode, 'OP_TUCK'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_TUCK');
                            }
                            $vch = $this->mainStack->top(-1);
                            $this->mainStack->insert($this->mainStack->end() - 2, $vch);
                            break;

                        case $this->isOp($opCode, 'OP_SIZE'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_SIZE');
                            }
                            // todo
                            // Different types could be returned here

                            $vch = $this->mainStack->top(-1);
                            $size = pack("H*", $this->math->decHex(strlen($vch)));

                            $this->mainStack->push($size);
                            break;

                        case $this->isOp($opCode, 'OP_EQUAL'):
                        case $this->isOp($opCode, 'OP_EQUALVERIFY'):
                        //case $this->isOp($opCode, 'OP_NOTEQUAL'): // use OP_NUMNOTEQUAL
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation OP_EQUAL');
                            }
                            $vch1 = $this->mainStack->top(-2);
                            $vch2 = $this->mainStack->top(-1);

                            $equal = $vch1 === $vch2;

                            // OP_NOTEQUAL is disabled
                            //if ($this->isOp($opCode, 'OP_NOTEQUAL')) {
                            //    $equal = !$equal;
                            //}

                            $this->mainStack->pop();
                            $this->mainStack->pop();
                            $this->mainStack->push(($equal ? true : false));

                            if ($this->isOp($opCode, 'OP_EQUALVERIFY')) {
                                if ($equal) {
                                    $this->mainStack->pop();
                                } else {
                                    throw new \Exception('Error EQUALVERIFY');
                                }
                            }
                            break;

                        case $this->isOp($opCode, 'OP_1ADD'):
                        case $this->isOp($opCode, 'OP_1SUB'):
                        case $this->isOp($opCode, 'OP_NEGATE'):
                        case $this->isOp($opCode, 'OP_ABS'):
                        case $this->isOp($opCode, 'OP_NOT'):
                        case $this->isOp($opCode, 'OP_0NOTEQUAL'):
                            if ($this->mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation 1ADD');
                            }
                            $num = $this->mainStack->top(-1);

                            switch ($opCode) {
                                case $this->isOp($opCode, 'OP_1ADD'):
                                    $num = $this->math->add($num, '1');
                                    break;
                                case $this->isOp($opCode, 'OP_1SUB'):
                                    $num = $this->math->sub($num, '1');
                                    break;
                                case $this->isOp($opCode, 'OP_NEGATE'):
                                    $num = $this->math->sub(0, $num);
                                    break;
                                case $this->isOp($opCode, 'OP_ABS'):
                                    if ($this->math->cmp($num, '0') < 0) {
                                        $num = $this->math->sub(0, $num);
                                    }
                                    break;
                                case $this->isOp($opCode, 'OP_NOT'):
                                    $num = ($this->math->cmp($num, '0') == 0);
                                    break;
                                case $this->isOp($opCode, 'OP_0NOTEQUAL'):
                                    $num = ($this->math->cmp($num, '0') !== 0);
                                    break;
                                default:
                                    throw new \Exception('Invalid Opcode');
                                break;
                            }

                            $this->mainStack->pop();
                            $this->mainStack->push($num);
                            break;


                        case $this->isOp($opCode, 'OP_ADD'):
                        case $this->isOp($opCode, 'OP_SUB'):
                        case $this->isOp($opCode, 'OP_BOOLAND'):
                        case $this->isOp($opCode, 'OP_BOOLOR'):
                        case $this->isOp($opCode, 'OP_NUMEQUAL'):
                        case $this->isOp($opCode, 'OP_NUMEQUALVERIFY'):
                        case $this->isOp($opCode, 'OP_NUMNOTEQUAL'):
                        case $this->isOp($opCode, 'OP_LESSTHAN'):
                        case $this->isOp($opCode, 'OP_GREATERTHAN'):
                        case $this->isOp($opCode, 'OP_LESSTHANOREQUAL'):
                        case $this->isOp($opCode, 'OP_GREATERTHANOREQUAL'):
                        case $this->isOp($opCode, 'OP_MIN'):
                        case $this->isOp($opCode, 'OP_MAX'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation (greater than)');
                            }
                            $num1 = $this->mainStack->top(-2);
                            $num2 = $this->mainStack->top(-1);
                            $_bn0 = '0';

                            switch ($opCode) {
                                case $this->isOp($opCode, 'OP_ADD'):
                                    $num = $this->math->add($num1, $num2);
                                    break;
                                case $this->isOp($opCode, 'OP_SUB'):
                                    $num = $this->math->sub($num1, $num2);
                                    break;
                                case $this->isOp($opCode, 'OP_BOOLAND'):
                                    $num = ($this->math->cmp($num1, $_bn0) !== 0 && $this->math->cmp($num2, $_bn0) !== 0 );
                                    break;
                                case $this->isOp($opCode, 'OP_BOOLOR'):
                                    $num = ($this->math->cmp($num1, $_bn0) !== 0 || $this->math->cmp($num2, $_bn0) !== 0 );
                                    break;
                                case $this->isOp($opCode, 'OP_NUMEQUAL'):
                                    $num = ($this->math->cmp($num1, $num2) == 0);
                                    break;
                                case $this->isOp($opCode, 'OP_NUMEQUALVERIFY'):
                                    $num = ($this->math->cmp($num1, $num2) == 0);
                                    break;
                                case $this->isOp($opCode, 'OP_NUMNOTEQUAL'):
                                    $num = ($this->math->cmp($num1, $num2) !== 0);
                                    break;
                                case $this->isOp($opCode, 'OP_LESSTHAN'):
                                    $num = ($this->math->cmp($num1, $num2) < 0);
                                    break;
                                case $this->isOp($opCode, 'OP_GREATERTHAN'):
                                    $num = ($this->math->cmp($num1, $num2) > 0);
                                    break;
                                case $this->isOp($opCode, 'OP_LESSTHANOREQUAL'):
                                    $num = ($this->math->cmp($num1, $num2) <= 0);
                                    break;
                                case $this->isOp($opCode, 'OP_GREATERTHANOREQUAL'):
                                    $num = ($this->math->cmp($num1, $num2) >= 0);
                                    break;
                                case $this->isOp($opCode, 'OP_MIN'):
                                    $num = ($this->math->cmp($num1, $num2) <= 0) ? $num1 : $num2;
                                    break;
                                case $this->isOp($opCode, 'OP_MAX'):
                                    $num = ($this->math->cmp($num1, $num2) >= 0) ? $num1 : $num2;
                                    break;
                                default:
                                    throw new \Exception('Invalid opcode in maths ops');
                                break;
                            }
                            $this->mainStack->pop();
                            $this->mainStack->pop();
                            $this->mainStack->push($num);
                            if ($this->isOp($opCode, 'OP_NUMEQUALVERIFY')) {
                                if ($this->castToBool($this->mainStack->top(-1))) {
                                    $this->mainStack->pop();
                                } else {
                                    throw new \Exception('NUM EQUAL VERIFY error');
                                }
                            }
                            break;

                        case $this->isOp($opCode, 'OP_WITHIN'):
                            if ($this->mainStack->size() < 3) {
                                throw new \Exception('Invalid stack operation');
                            }
                            $num1 = $this->mainStack->top(-3);
                            $num2 = $this->mainStack->top(-2);
                            $num3 = $this->mainStack->top(-1);
                            $value = ($this->math->cmp($num2, $num1) <= 0 && $this->math->cmp($num1, $num3) < 0);
                            $this->mainStack->pop();
                            $this->mainStack->pop();
                            $this->mainStack->pop();
                            $this->mainStack->push($value ? true : false);
                            break;

                        case $this->isOp($opCode, 'OP_RIPEMD160'):
                        case $this->isOp($opCode, 'OP_SHA1'):
                        case $this->isOp($opCode, 'OP_SHA256'):
                        case $this->isOp($opCode, 'OP_HASH160'):
                        case $this->isOp($opCode, 'OP_HASH256'):
                            if ($this->mainStack->size() < 1) {
                                throw new \Exception('Invalid stack operation');
                            }
                            $vch = $this->mainStack->top(-1);
                            $hashLen = (
                                $this->isOp($opCode, 'OP_RIPEMD160')
                                || $this->isOp($opCode, 'OP_SHA1')
                                || $this->isOp($opCode, 'OP_HASH160')
                            ) ? 20 : 32;

                            if ($this->isOp($opCode, 'OP_RIPEMD160')) {
                                $hash = Hash::ripemd160($vch, true);
                            } elseif ($this->isOp($opCode, 'OP_SHA1')) {
                                $hash = Hash::sha1($vch, true);
                            } elseif ($this->isOp($opCode, 'OP_SHA256')) {
                                $hash = Hash::sha256($vch, true);
                            } elseif ($this->isOp($opCode, 'OP_HASH160')) {
                                $hash = Hash::sha256ripe160($vch->serialize('hex'), true);
                            } elseif ($this->isOp($opCode, 'OP_HASH256')) {
                                $hash = Hash::sha256d($vch, true);
                            }

                            $this->mainStack->pop();
                            $this->mainStack->push($hash);
                            break;

                        case $this->isOp($opCode, 'OP_CODESEPARATOR'):
                            if ($this->mainStack->size() < 4) {
                                throw new \Exception('Invalid stack operation');
                            }
                            $this->hashStartPos = $pos;
                            break;

                        case $this->isOp($opCode, 'OP_CHECKSIG'):
                        case $this->isOp($opCode, 'OP_CHECKSIGVERIFY'):
                            if ($this->mainStack->size() < 2) {
                                throw new \Exception('Invalid stack operation');
                            }

                            $vchPubKey = $this->mainStack->top(-1);
                            $vchSig = $this->mainStack->top(-2);

                            if (!$this->checkSignatureEncoding($vchSig) || !$this->checkPublicKeyEncoding($vchPubKey)) {
                                return false;
                            }

                            $signature = Signature::fromHex($vchSig);
                            $publicKey = PublicKey::fromHex($vchPubKey);

                            $scriptCode= new Buffer(substr($script, $this->hashStartPos, $posScriptEnd));
                            $script    = new Script($scriptCode);
                            $sigHash   = $this->transaction->signatureHash()->calculate($script, $this->inputToSign, $signature->getSighashType());
                            $signer    = new Signer($this->math, $this->generator);

                           // $hash = $this->transaction->get
                            $hash      = new Buffer();
                            $success   = $signer->verify($publicKey, $sigHash, $signature);
                            $this->mainStack->pop();
                            $this->mainStack->pop();
                            $this->mainStack->push($success ? $this->constTrue : $this->constFalse);

                            if ($this->isOp($opCode, 'OP_CHECKSIGVERIFY')) {
                                if ($success) {
                                    $this->mainStack->pop();
                                } else {
                                    throw new \Exception('Checksig verify');
                                }
                            }

                            break;


                        /**
                        case $this->isOp($opCode, 'OP_IFDUPA'):
                            if ($this->mainStack->size() < 4) {
                                throw new \Exception('Invalid stack operation');
                            }
                            break;
                        */
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
            echo "E: ".$e->getMessage()."\n";
            return false;
        }
    }
}
