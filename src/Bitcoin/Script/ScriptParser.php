<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Math\Math;

class ScriptParser
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @var int
     */
    private $ptr;

    /**
     * @var string
     */
    private $scriptRaw;

    /**
     * @param Math $math
     * @param ScriptInterface $script
     */
    public function __construct(Math $math, ScriptInterface $script)
    {
        $this->math = $math;
        $this->script = $script;
        $this->scriptRaw = $script->getBuffer()->getBinary();
        $this->ptr = 0;
    }

    /**
     * @return int
     */
    private function getNextOp()
    {
        return ord($this->scriptRaw[$this->ptr++]);
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->ptr;
    }

    /**
     * @return int
     */
    public function getEndPos()
    {
        return $this->script->getBuffer()->getSize();
    }

    /**
     * @param $size
     * @return bool
     */
    public function validateSize($size)
    {
        $pdif = ($this->getEndPos() - $this->getPosition());
        if (($pdif < 0) || ($pdif < $size)) {
            return false;
        }

        return true;
    }

    /**
     * @param $format
     * @param $strSize
     * @return array|bool
     */
    private function unpackSize($format, $strSize)
    {
        if ($this->getEndPos() - $this->getPosition() < $strSize) {
            return false;
        }
        $size = unpack($format, substr($this->scriptRaw, $this->getPosition(), $strSize));
        $size = $size[1];
        $this->ptr += $strSize;

        return $size;
    }

    /**
     * @param $opCode
     * @param Buffer|null $pushData
     * @return bool
     */
    public function next(&$opCode, &$pushData = null)
    {
        $opcodes = $this->script->getOpcodes();
        $opCode = $opcodes->getOpByName('OP_INVALIDOPCODE');

        if ($this->math->cmp($this->getPosition(), $this->getEndPos()) >= 0) {
            return false;
        }

        $opCode = $this->getNextOp();

        if ($opcodes->cmp($opCode, 'OP_PUSHDATA4') <= 0) {
            if ($opcodes->cmp($opCode, 'OP_PUSHDATA1') < 0) {
                $size = $opCode;
            } else if ($opcodes->isOp($opCode, 'OP_PUSHDATA1')) {
                $size = $this->unpackSize("C", 1);
            } else if ($opcodes->isOp($opCode, 'OP_PUSHDATA2')) {
                $size = $this->unpackSize("v", 2);
            } else {
                $size = $this->unpackSize("V", 4);
            }

            if ($size === false || $this->validateSize($size) === false) {
                return false;
            }

            $pushData = substr($this->scriptRaw, $this->ptr, $size);
            $this->ptr += $size;
        }

        return true;
    }

    /**
     * @return array
     */
    public function parse()
    {
        $data = array();

        $opCode = null;
        $pushData = null;

        while ($this->next($opCode, $pushData)) {
            if ($opCode < 1) {
                $push = Buffer::hex('00');
            } elseif ($opCode <= 78) {
                $push = new Buffer($pushData);
            } else {
                // None of these are pushdatas, so just an opcode
                $push = $this->script->getOpcodes()->getOp($opCode);
            }

            $data[] = $push;
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getHumanReadable()
    {
        $array = array_map(
            function ($value) {
                $r = ($value instanceof Buffer)
                    ? $value->serialize('hex')
                    : $value;
                return $r;
            },
            $this->parse()
        );

        return implode(" ", $array);
    }
}
