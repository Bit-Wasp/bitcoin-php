<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Buffertools\Buffer;
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
     * @param Buffer|null $buffer
     */
    public function __construct(Math $math, Opcodes $opcodes, Buffer $buffer = null)
    {
        if ($buffer != null) {
            $this->script = $buffer->getBinary();
        }

        $this->math = $math;
        $this->opcodes = $opcodes;
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
        $this->script .= pack('H*', dechex($code));
        return $this;
    }

    /**
     * Push data into the stack.
     *
     * @param $data
     * @return $this
     * @throws \Exception
     */
    public function push(Buffer $data)
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
     * @param ScriptInterface $script
     * @return $this
     */
    public function concat(ScriptInterface $script)
    {
        $this->script .= $script->getBinary();
        return $this;
    }

    /**
     * @return Script
     */
    public function getScript()
    {
        return new Script(new Buffer($this->script, null, $this->math), $this->opcodes);
    }
}
