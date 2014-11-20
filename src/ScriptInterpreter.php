<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 20/11/14
 * Time: 12:50
 */

namespace Bitcoin;


class ScriptInterpreter implements ScriptInterpreterInterface
{

    /**
     * @var Script
     */
    protected $script;

    /**
     * @var
     */
    protected $stack;

    /**
     * @var
     */
    protected $checkDisabledOpcodes;

    /**
     * @var
     */
    protected $maxBytes;

    /**
     * @var
     */
    protected $maxPushBytes;

    /**
     * @var
     */
    protected $maxOpCodes;

    /**
     * @param Script $script
     */
    public function __construct(Script $script)
    {
        $this->script = $script;
        $this->rOpCodes = $this->script->getRegisteredOpCodes();
        $this->setCheckDisabledOpcodes(true);
        $this->setMaxBytes(10000);
        $this->setMaxPushBytes(520);
        $this->setMaxOpCodes(200);
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
        $this->maxOpCodes($limit);
        return $this;
    }

    /**
     * Evaluate a script, when given a transaction, the index, and sighash type.
     *
     * @param TransactionInterface $transaction
     * @param $index
     * @param $sighash_type
     */
    public function run(TransactionInterface $transaction, $index, $sighash_type)
    {

        $position = 0;
        $script = $this->script->getHex(true);
        $scriptLen = strlen($script);

        while ($position < $scriptLen) {

            $opCode = $this->rOpCodes[ord(substr($this->script, $position, 1))];

            switch ($opCode)
            {
                // Check for disabled opcodes
                case 'OP_CAT':
                case 'OP_SUBSTR':
                case 'OP_LEFT':
                case 'OP_RIGHT':
                case 'OP_INVERT':
                case 'OP_AND':
                case 'OP_OR':
                case 'OP_XOR':
                case 'OP_2MUL':
                case 'OP_2DIV':
                case 'OP_MUL':
                case 'OP_DIV':
                case 'OP_MOD':
                case 'OP_LSHIFT':
                case 'OP_RSHIFT':
                    return false;

                // Proven unspendable
                case 'OP_RETURN':
                    return false;
                    break;

                // No operation
                case 'OP_NOP':
                case 'OP_NOP1': case 'OP_NOP2': case 'OP_NOP3': case 'OP_NOP4': case 'OP_NOP5':
                case 'OP_NOP6': case 'OP_NOP7': case 'OP_NOP8': case 'OP_NOP9': case 'OP_NOP10':
                break;

                // Verify first element in stack is not false
                case 'OP_VERIFY':
                    if (empty($this->mainStack))
                        return false;
                    if(false == $this->castToBool($this->popFromMainStack()))
                        return false;
                    $nextPosition = $position + 1;
                    break;
            }
        }
    }

};