<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Interpreter\Number;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class ScriptCreator
{
    /**
     * @var string
     */
    private $script = '';

    /**
     * @var Opcodes
     */
    private $opcodes;

    /**
     * @var Math
     */
    private $math;

    /**
     * @param Math $math
     * @param Opcodes $opcodes
     * @param BufferInterface|null $buffer
     */
    public function __construct(Math $math, Opcodes $opcodes, BufferInterface $buffer = null)
    {
        if ($buffer !== null) {
            $this->script = $buffer->getBinary();
        }

        $this->math = $math;
        $this->opcodes = $opcodes;
    }

    /**
     * Add a data-push instruction to the script,
     * pushing x bytes of $data from $data, with
     * the appropriate marker for the different
     * PUSHDATA opcodes.
     *
     * @param BufferInterface $data
     * @return $this
     */
    public function push(BufferInterface $data)
    {
        $length = $data->getSize();

        if ($length < Opcodes::OP_PUSHDATA1) {
            $this->script .= pack('C', $length) . $data->getBinary();
        } else {
            if ($length <= 0xff) {
                $lengthSize = 1;
                $code = 'C';
            } elseif ($length <= 0xffff) {
                $lengthSize = 2;
                $code = 'S';
            } else {
                $lengthSize = 4;
                $code = 'V';
            }

            $opCode = constant("BitWasp\\Bitcoin\\Script\\Opcodes::OP_PUSHDATA" . $lengthSize);
            $this->script .= pack('C', $opCode) . pack($code, $length) . $data->getBinary();
        }

        return $this;
    }

    /**
     * Concatenate $script onto $this.
     * @param ScriptInterface $script
     * @return $this
     */
    public function concat(ScriptInterface $script)
    {
        $this->script .= $script->getBinary();
        return $this;
    }

    /**
     * This function accepts an array of elements, builds
     * an intermediate script composed of the items in $sequence,
     * and concatenates it in one step.
     *
     * The allowed types are:
     *  - opcode (integer form)
     *  - script number (Number class)
     *  - data (BufferInterface)
     *  - script (ScriptInterface)
     *
     * @param int[]|\BitWasp\Bitcoin\Script\Interpreter\Number[]|BufferInterface[] $sequence
     * @return $this
     */
    public function sequence(array $sequence)
    {
        $new = new self($this->math, $this->opcodes, null);
        foreach ($sequence as $operation) {
            if (is_int($operation)) {
                if (!$this->opcodes->offsetExists($operation)) {
                    throw new \RuntimeException('Unknown opcode');
                }

                $new->script .= chr($operation);
            } elseif ($operation instanceof Number) {
                $new->push($operation->getBuffer());
            } elseif ($operation instanceof BufferInterface) {
                $new->push($operation);
            } elseif ($operation instanceof ScriptInterface) {
                $new->concat($operation);
            } else {
                throw new \RuntimeException('Value must be an opcode/BufferInterface/Number');
            }
        }

        $this->concat($new->getScript());
        return $this;
    }

    /**
     * This function accepts an integer, and adds the appropriate
     * data-push instruction to the script, minimally encoding it
     * where possible.
     *
     * @param int $n
     * @return $this
     */
    public function int(int $n)
    {
        if ($n === 0) {
            $this->script .= chr(Opcodes::OP_0);
        } else if ($n === -1 || ($n >= 1 && $n <= 16)) {
            $this->script .= chr(\BitWasp\Bitcoin\Script\encodeOpN($n));
        } else {
            $this->push(Number::int($n)->getBuffer());
        }

        return $this;
    }

    /**
     * Takes a list of opcodes (the name as a string)
     * and adds the opcodes to the script.
     *
     * @param string... $opNames
     * @return $this
     */
    public function op(string... $opNames)
    {
        $opCodes = [];
        foreach ($opNames as $opName) {
            $opCodes[] = $this->opcodes->getOpByName($opName);
        }

        return $this->sequence($opCodes);
    }

    /**
     * Takes a list of opcodes (in integer form) and
     * adds them to the script.
     *
     * @param int ...$opcodes
     * @return $this
     */
    public function opcode(int ...$opcodes)
    {
        $this->sequence($opcodes);
        return $this;
    }

    /**
     * Takes a list of data elements and adds the
     * push-data instructions to the script.
     *
     * @param BufferInterface ...$dataList
     * @return $this
     */
    public function data(BufferInterface ...$dataList)
    {
        $this->sequence($dataList);
        return $this;
    }

    /**
     * Generates a script based on the current state.
     * @return ScriptInterface
     */
    public function getScript(): ScriptInterface
    {
        return new Script(new Buffer($this->script), $this->opcodes);
    }
}
