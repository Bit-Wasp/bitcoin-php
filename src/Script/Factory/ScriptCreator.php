<?php

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Interpreter\Number;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializable;
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
     * Add an opcode to the script
     *
     * @param string $name
     * @return $this
     */
    public function op($name)
    {
        $code = $this->opcodes->getOpByName($name);
        $this->script .= chr($code);
        return $this;
    }

    /**
     * Push data into the stack.
     *
     * @param $data
     * @return $this
     * @throws \Exception
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
     * @param int $n
     * @return $this
     */
    public function int($n)
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
     * @param Serializable $object
     * @return $this
     */
    public function pushSerializable(Serializable $object)
    {
        $this->push($object->getBuffer());
        return $this;
    }

    /**
     * @param ScriptInterface $script
     * @return $this
     */
    public function concat(ScriptInterface $script)
    {
        $this->script .= $script->getBinary();
        return $this;
    }

    /**
     * @return ScriptInterface
     */
    public function getScript()
    {
        return new Script(new Buffer($this->script, null, $this->math), $this->opcodes);
    }
}
