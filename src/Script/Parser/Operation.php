<?php

namespace BitWasp\Bitcoin\Script\Parser;

use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Buffertools\Buffer;

class Operation
{
    /**
     * @var bool
     */
    private $push;

    /**
     * @var int
     */
    private $opCode;

    /**
     * @var Buffer|null
     */
    private $pushData;

    /**
     * ScriptExec constructor.
     * @param $opCode
     * @param Buffer|null $pushData
     */
    public function __construct($opCode, Buffer $pushData = null)
    {
        $this->push = $opCode >= 0 && $opCode <= Opcodes::OP_PUSHDATA4;
        $this->opCode = $opCode;
        $this->pushData = $pushData;
    }

    /**
     * @return bool
     */
    public function isPush()
    {
        return $this->push;
    }

    /**
     * @return int
     */
    public function getOp()
    {
        return $this->opCode;
    }

    /**
     * @return Buffer|null
     */
    public function getData()
    {
        return $this->pushData;
    }
}
