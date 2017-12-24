<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class Conditional
{
    /**
     * @var int
     */
    private $opcode;

    /**
     * @var bool
     */
    private $value;

    /**
     * @var null
     */
    private $providedBy = null;

    /**
     * Conditional constructor.
     * @param int $opcode
     */
    public function __construct(int $opcode)
    {
        if ($opcode !== Opcodes::OP_IF && $opcode !== Opcodes::OP_NOTIF) {
            throw new \RuntimeException("Opcode for conditional is only IF / NOTIF");
        }

        $this->opcode = $opcode;
    }

    /**
     * @return int
     */
    public function getOp(): int
    {
        return $this->opcode;
    }

    /**
     * @param bool $value
     */
    public function setValue(bool $value)
    {
        $this->value = $value;
    }

    /**
     * @return bool
     */
    public function hasValue(): bool
    {
        return null !== $this->value;
    }

    /**
     * @return bool
     */
    public function getValue(): bool
    {
        if (null === $this->value) {
            throw new \RuntimeException("Value not set on conditional");
        }

        return $this->value;
    }

    /**
     * @param Checksig $checksig
     */
    public function providedBy(Checksig $checksig)
    {
        $this->providedBy = $checksig;
    }

    /**
     * @return BufferInterface[]
     */
    public function serialize(): array
    {
        if ($this->hasValue() && null === $this->providedBy) {
            return [$this->value ? new Buffer("\x01") : new Buffer()];
        }

        return [];
    }
}
