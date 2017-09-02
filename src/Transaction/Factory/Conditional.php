<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Script\Opcodes;

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
     * Conditional constructor.
     * @param int $opcode
     */
    public function __construct($opcode)
    {
        if ($opcode !== Opcodes::OP_IF && $opcode !== Opcodes::OP_NOTIF) {
            throw new \RuntimeException("Opcode for conditional is only IF / NOTIF");
        }

        $this->opcode = $opcode;
    }

    /**
     * @param bool $value
     */
    public function setValue($value)
    {
        if (!is_bool($value)) {
            throw new \RuntimeException("Invalid value for conditional");
        }

        $this->value = $value;
    }

    /**
     * @return bool
     */
    public function hasValue()
    {
        return null !== $this->value;
    }

    /**
     * @return bool
     */
    public function getValue()
    {
        if (null === $this->value) {
            throw new \RuntimeException("Value not set on conditional");
        }

        return $this->value;
    }
}
