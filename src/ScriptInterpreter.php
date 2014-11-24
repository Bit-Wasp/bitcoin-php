<?php

namespace Bitcoin;


class ScriptInterpreter implements ScriptInterpreterInterface
{

    /**
     * @var Script
     */
    private $script;

    /**
     *
     */
    protected $execStack;

    /**
     * @var ScriptStack
     */
    protected $mainStack;

    /**
     * Alt Stack
     * @var ScriptStack
     */
    protected $altStack;

    /**
     * Position of codeseparator, for calcualting sighash
     * @var
     */
    protected $hashStart;

    /**
     * @var int
     */
    protected $opCount;

    /**
     * @var bool
     */
    protected $checkDisabledOpcodes;

    /**
     * @var int
     */
    protected $maxBytes;

    /**
     * @var int
     */
    protected $maxPushBytes;

    /**
     * @var int
     */
    protected $maxOpCodes;

    /**
     * @var bool
     */
    protected $requireLowestPushdata;

    /**
     * @param Script $script
     */
    public function __construct(Script $script)
    {
        $this->script    = $script;
        $this->mainStack = new ScriptStack;
        $this->altStack  = new ScriptStack;

        // Set up current limits
        $this->setCheckDisabledOpcodes(true);
        $this->setMaxBytes(10000);
        $this->setMaxPushBytes(520);
        $this->setMaxOpCodes(200);
        $this->requireLowestPushdata(true);
    }

    /**
     * @inheritdoc
     */
    public function checkDisabledOpcodes()
    {
        return $this->checkDisabledOpcodes;
    }

    /**
     * Set whether interpreter should check for disabled/unsafe opcodes
     *
     * @param $setting
     * @return $this
     */
    public function setCheckDisabledOpcodes($setting)
    {
        $this->checkDisabledOpcodes = $setting;
        return $this;
    }

    /**
     * @return array
     */
    public function getDisabledOpcodes()
    {
        return array('OP_CAT', 'OP_SUBSTR', 'OP_LEFT', 'OP_RIGHT',
        'OP_INVERT', 'OP_AND', 'OP_OR', 'OP_XOR', 'OP_2MUL', 'OP_2DIV',
        'OP_MUL', 'OP_DIV', 'OP_MOD', 'OP_LSHIFT');

    }
    /**
     * @return bool
     */
    public function requireLowestPushdata()
    {
        return $this->requireLowestPushdata;
    }

    /**
     * @param $bool
     * @return $this
     */
    public function setRequestLowestPushdata($bool)
    {
        $this->requireLowestPushdata = $bool;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMaxBytes()
    {
        return $this->maxBytes;
    }

    /**
     * Set limit on the size of the script
     *
     * @param $limit
     * @return $this
     */
    public function setMaxBytes($limit)
    {
        $this->maxBytes = $limit;
        return $this;
    }

    /**
     * Get max number of bytes in pushdata
     * @return mixed
     */
    public function getMaxPushBytes()
    {
        return $this->maxPushBytes;
    }

    /**
     * Set Maximum number of bytes in pushdata
     *
     * @param $limit
     * @return $this
     */
    public function setMaxPushBytes($limit)
    {
        $this->maxPushBytes = $limit;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMaxOpCodes()
    {
        return $this->maxOpCodes;
    }

    /**
     * Set Max number of opcodes in a script
     *
     * @param $limit
     * @return $this
     */
    public function setMaxOpCodes($limit)
    {
        $this->maxOpCodes = $limit;
        return $this;
    }

    // todo checkMinimalPush

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

    public function castToBool($value)
    {
        if ($value) {
            return true;
        }

        return false;
    }

    /**
     * Evaluate a script, when given a transaction, the index, and sighash type.
     *
     * @param TransactionInterface $transaction
     * @param $index
     * @param $sighash_type
     */
    //public function run(TransactionInterface $transaction, $index, $sighash_type)
    public function run()
    {
        $position = 0;

        try {
            foreach ($this->script->parse() as $op) {
                if ($op instanceof Buffer) {
                    $this->mainStack->push($op);
                } else {

                    if ($this->checkDisabledOpcodes() and in_array($op, $this->getDisabledOpcodes())) {
                        throw new ScriptRuntimeException('Used disabled opcode: ' . $op);
                    }

                    switch ($op) {

                        case 'OP_1NEGATE': // 79
                        case 'OP_1':
                        case 'OP_2':
                        case 'OP_3':
                        case 'OP_4':
                        case 'OP_5':
                        case 'OP_6':
                        case 'OP_7':
                        case 'OP_8':
                        case 'OP_9':
                        case 'OP_10':
                        case 'OP_11':
                        case 'OP_12':
                        case 'OP_13':
                        case 'OP_14':
                        case 'OP_15':
                        case 'OP_16':       // 96
                            $num = $this->script->getOpCode($op) - $this->script->getOpCode('OP_1') + 1;
                            $this->mainStack->push($num);
                            break;

                        // No operation
                        case 'OP_NOP':
                        case 'OP_NOP1':
                        case 'OP_NOP2':
                        case 'OP_NOP3':
                        case 'OP_NOP4':
                        case 'OP_NOP5':
                        case 'OP_NOP6':
                        case 'OP_NOP7':
                        case 'OP_NOP8':
                        case 'OP_NOP9':
                        case 'OP_NOP10':
                            break;

                        // Proven unspendable
                        case 'OP_RETURN':
                            return false;
                            break;

                        case 'OP_RIPEMD160':
                        case 'OP_SHA1':
                        case 'OP_SHA256':
                        case 'OP_HASH160':
                        case 'OP_HASH256':
                            $value = $this->mainStack->pop();
                            if ($op == 'OP_RIPEMD160') {
                                $hash = Hash::ripemd160($value);
                            } elseif ($op == 'OP_SHA1') {
                                $hash = Hash::sha1($value);
                            } elseif ($op == 'OP_SHA256') {
                                $hash = Hash::sha256($value);
                            } elseif ($op == 'OP_HASH160') {
                                $hash = Hash::sha256ripe160($value);
                            } else {
                                $hash = Hash::sha256d($value);
                            }
                            $buffer = new Buffer($hash);
                            $this->mainStack->push($buffer);
                            break;

                        // Verify first element in stack is not false
                        case 'OP_VERIFY':
                            if (empty($this->mainStack)) {
                                return false;
                            }
                            if (false == $this->castToBool($this->popFromMainStack())) {
                                return false;
                            }
                            $nextPosition = $position + 1;
                            break;

                        case 'OP_CHECKSIG':
                            break;

                        case 'OP_CHECKMULTISIG':
                            break;
                    }
                }
            }
        } catch (ScriptRuntimeException $e) {

        } catch (ScriptStackException $e) {

        }
    }
}
;

class ScriptRuntimeException extends \Exception
{
}

class ScriptStackException extends \Exception
{
}
