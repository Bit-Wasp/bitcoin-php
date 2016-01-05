<?php

namespace BitWasp\Bitcoin\Script\Parser;

use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class Parser implements \Iterator
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var BufferInterface
     */
    private $empty;

    /**
     * @var ScriptInterface
     */
    private $script;

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
     * @var Operation[]
     */
    private $array = array();

    /**
     * ScriptParser constructor.
     * @param Math $math
     * @param ScriptInterface $script
     */
    public function __construct(Math $math, ScriptInterface $script)
    {
        $this->math = $math;
        $buffer = $script->getBuffer();
        $this->data = $buffer->getBinary();
        $this->end = $buffer->getSize();
        $this->script = $script;
        $this->empty = new Buffer('', 0, $math);
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
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
     * @param int $ptr
     * @return Operation
     */
    private function doNext($ptr)
    {
        if ($this->math->cmp($this->position, $this->end) >= 0) {
            throw new \RuntimeException('Position exceeds end of script!');
        }

        $opCode = ord($this->data[$this->position++]);
        $pushData = $this->empty;
        $dataSize = 0;

        if ($opCode <= Opcodes::OP_PUSHDATA4) {
            if ($opCode < Opcodes::OP_PUSHDATA1) {
                $dataSize = $opCode;
            } else if ($opCode === Opcodes::OP_PUSHDATA1) {
                $dataSize = $this->unpackSize('C', 1);
            } else if ($opCode === Opcodes::OP_PUSHDATA2) {
                $dataSize = $this->unpackSize('v', 2);
            } else {
                $dataSize = $this->unpackSize('V', 4);
            }

            $delta = ($this->end - $this->position);
            if ($dataSize === false || $delta < 0 || $delta < $dataSize) {
                throw new \RuntimeException('Failed to unpack data from Script');
            }

            if ($dataSize > 0) {
                $pushData = new Buffer(substr($this->data, $this->position, $dataSize), $dataSize, $this->math);
            }

            $this->position += $dataSize;
        }

        $this->array[$ptr] = new Operation($opCode, $pushData, $dataSize);

        return $this->array[$ptr];
    }

    /**
     *
     */
    public function rewind()
    {
        $this->execPtr = 0;
    }

    /**
     * @return Operation
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
     * @return Operation
     */
    public function next()
    {
        $ptr = $this->execPtr;
        if (isset($this->array[$ptr])) {
            $this->execPtr++;
            return $this->array[$ptr];
        }

        return null;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return isset($this->array[$this->execPtr]) || $this->position < $this->end;
    }

    /**
     * @return Operation[]
     */
    public function decode()
    {
        $result = [];
        foreach ($this as $operation) {
            $result[] = $operation;
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getHumanReadable()
    {
        return implode(' ', array_map(
            function (Operation $operation) {
                return $operation->isPush()
                    ? $operation->getData()->getHex()
                    : $this->script->getOpcodes()->getOp($operation->getOp());
            },
            $this->decode()
        ));
    }
}
