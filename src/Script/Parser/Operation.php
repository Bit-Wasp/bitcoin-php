<?php

declare(strict_types=1);

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
    public function __construct(int $opCode, BufferInterface $pushData, int $pushDataSize = 0)
    {
        $this->push = $opCode >= 0 && $opCode <= Opcodes::OP_PUSHDATA4;
        $this->opCode = $opCode;
        $this->pushData = $pushData;
        $this->pushDataSize = $pushDataSize;
    }

    /**
     * @return BufferInterface|int
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
    public function isPush(): bool
    {
        return $this->push;
    }

    /**
     * @return bool
     */
    public function isLogical(): bool
    {
        return !$this->isPush() && in_array($this->opCode, self::$logical);
    }


    /**
     * @return int
     */
    public function getOp(): int
    {
        return $this->opCode;
    }

    /**
     * @return BufferInterface
     */
    public function getData(): BufferInterface
    {
        return $this->pushData;
    }

    /**
     * @return int
     */
    public function getDataSize(): int
    {
        if (!$this->push) {
            throw new \RuntimeException("Op wasn't a push operation");
        }

        return $this->pushDataSize;
    }
}
