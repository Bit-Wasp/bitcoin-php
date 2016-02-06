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
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;

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
            } else {
                throw new \RuntimeException('Input was neither an opcode or BufferInterfacecc');
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
        $parsed = new Parser('', $this->math);

        /** Note that larger integers are serialized without flipping bits - Big endian */

        if ($length < $this->opcodes->getOpByName('OP_PUSHDATA1')) {
            $varInt = Buffertools::numToVarInt($length);
            $data = new Buffer($varInt->getBinary() . $data->getBinary(), null, $this->math);
            $parsed->writeBytes($data->getSize(), $data);
        } else {
            if ($length <= 0xff) {
                $lengthSize = 1;
            } elseif ($length <= 0xffff) {
                $lengthSize = 2;
            } else {
                $lengthSize = 4;
            }

            $op = $this->opcodes->getOpByName('OP_PUSHDATA' . $lengthSize);
            $parsed
                ->writeBytes(1, Buffer::int($op))
                ->writeBytes($lengthSize, Buffer::int($length), true)
                ->writeBytes($length, $data);
        }

        $this->script .= $parsed->getBuffer()->getBinary();
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
            $this->script .= Number::int($n)->getBinary();
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
     * @param Serializable[] $serializable
     * @return $this
     */
    public function pushSerializableArray(array $serializable)
    {
        foreach ($serializable as $object) {
            $this->pushSerializable($object);
        }

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
