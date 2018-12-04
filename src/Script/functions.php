<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script;

function decodeOpN(int $op): int
{
    if ($op === Opcodes::OP_0) {
        return 0;
    }

    if (!($op === Opcodes::OP_1NEGATE || $op >= Opcodes::OP_1 && $op <= Opcodes::OP_16)) {
        throw new \RuntimeException("Invalid opcode");
    }

    return $op - (Opcodes::OP_1 - 1);
}

function encodeOpN(int $op): int
{
    if ($op === 0) {
        return Opcodes::OP_0;
    }

    if (!($op === -1 || $op >= 1 && $op <= 16)) {
        throw new \RuntimeException("Invalid value");
    }

    return Opcodes::OP_1 + $op - 1;
}
