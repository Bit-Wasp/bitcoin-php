<?php

namespace BitWasp\Bitcoin\Script\Parser;

use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Buffertools\BufferInterface;

class Operation
{
    /**
     * @var int[]
     */
    protected static $logical = [
        Opcodes::OP_IF, Opcodes::OP_NOTIF, Opcodes::OP_ELSE, Opcodes::OP_ENDIF,
    ];

    /**
     * @var bool
     */
    private $push;

    /**
     * @var int
     */
    private $opCode;

    /**
     * @var BufferInterface
     */
    private $pushData;

    /**
     * @var int
     */
    private $pushDataSize;

    /**
     * Operation constructor.
     * @param int $opCode
     * @param BufferInterface $pushData
     * @param int $pushDataSize
     */
    public function __construct($opCode, BufferInterface $pushData, $pushDataSize = 0)
    {
        $this->push = $opCode >= 0 && $opCode <= Opcodes::OP_PUSHDATA4;
        $this->opCode = $opCode;
        $this->pushData = $pushData;
        $this->pushDataSize = $pushDataSize;
    }

    /**
     * @return string
     */
    public function encode()
    {
        if ($this->push) {
            return $this->pushData;
        } else {
            return $this->opCode;
        }
    }

    /**
     * @return bool
     */
    public function isPush()
    {
        return $this->push;
    }

    /**
     * @return bool
     */
    public function isLogical()
    {
        return !$this->isPush() && in_array($this->opCode, self::$logical);
    }


    /**
     * @return int
     */
    public function getOp()
    {
        return $this->opCode;
    }

    /**
     * @return BufferInterface
     */
    public function getData()
    {
        return $this->pushData;
    }

    /**
     * @return int
     */
    public function getDataSize()
    {
        if (!$this->push) {
            throw new \RuntimeException("Op wasn't a push operation");
        }

        return $this->pushDataSize;
    }
}
