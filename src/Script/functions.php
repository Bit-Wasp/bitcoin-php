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

function isOPSuccess(int $op): bool
{
    return $op === 80 || $op === 98 ||
    ($op >= 126 && $op <= 129) ||
    ($op >= 131 && $op <= 134) ||
    ($op >= 137 && $op <= 138) ||
    ($op >= 141 && $op <= 142) ||
    ($op >= 149 && $op <= 153) ||
    ($op >= 187 && $op <= 254);
}