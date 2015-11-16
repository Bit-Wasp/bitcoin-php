<?php

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffer;

class ScriptIterator implements \Iterator
{
    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var int
     */
    private $end = 0;

    /**
     * @var int
     */
    private $execPtr = 0;

    /**
     * @var string
     */
    private $data = '';

    /**
     * @var ScriptExec[]
     */
    private $array = array();

    /**
     * ScriptIterator constructor.
     * @param ScriptInterface $script
     */
    public function __construct(ScriptInterface $script)
    {
        $this->math = Bitcoin::getMath();
        $buffer = $script->getBuffer();
        $this->data = $buffer->getBinary();
        $this->end = $buffer->getSize();
        $this->script = $script;
    }

    /**
     * @param string $packFormat
     * @param integer $strSize
     * @return array|bool
     */
    private function unpackSize($packFormat, $strSize)
    {
        if ($this->end - $this->position < $strSize) {
            return false;
        }
        $size = unpack($packFormat, substr($this->data, $this->position, $strSize));
        $size = $size[1];
        $this->position += $strSize;

        return $size;
    }

    /**
     * @param int $size
     * @return bool
     */
    private function validateSize($size)
    {
        $pdif = ($this->end - $this->position);
        return ! ($pdif < 0 || $pdif < $size);
    }

    /**
     * @param int $ptr
     * @return ScriptExec
     */
    private function doNext($ptr)
    {
        if ($this->math->cmp($this->position, $this->end) >= 0) {
            throw new \RuntimeException('Position exceeds end of script!');
        }

        $opCode = ord($this->data[$this->position++]);
        $pushData = null;

        if ($opCode === Opcodes::OP_0) {
            $pushData = new Buffer('', 0);
        } elseif ($opCode <= Opcodes::OP_PUSHDATA4) {
            if ($opCode < Opcodes::OP_PUSHDATA1) {
                $size = $opCode;
            } else if ($opCode === Opcodes::OP_PUSHDATA1) {
                $size = $this->unpackSize('C', 1);
            } else if ($opCode === Opcodes::OP_PUSHDATA2) {
                $size = $this->unpackSize('v', 2);
            } else {
                $size = $this->unpackSize('V', 4);
            }

            if ($size === false || $this->validateSize($size) === false) {
                throw new \RuntimeException('Failed to unpack data from Script');
            }

            $pushData = new Buffer(substr($this->data, $this->position, $size), $size, $this->math);
            $this->position += $size;
        }

        $this->array[$ptr] = $result = new ScriptExec($opCode, $pushData);

        return $result;
    }

    /**
     *
     */
    public function rewind()
    {
        $this->execPtr = 0;
    }

    /**
     * @return ScriptExec
     */
    public function current()
    {
        if (isset($this->array[$this->execPtr])) {
            $exec = $this->array[$this->execPtr];
        } else {
            $exec = $this->doNext($this->execPtr);
        }

        return $exec;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->execPtr;
    }

    /**
     * @return ScriptExec
     */
    public function next()
    {
        return $this->array[$this->execPtr++];
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return isset($this->array[$this->execPtr]) || $this->position < $this->end;
    }
}
