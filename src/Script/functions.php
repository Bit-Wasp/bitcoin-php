<?php

namespace BitWasp\Bitcoin\Script;

function decodeOpN($op)
{
    if ($op === Opcodes::OP_0) {
        return 0;
    }

    assert($op === Opcodes::OP_1NEGATE || $op >= Opcodes::OP_1 && $op <= Opcodes::OP_16);
    return (int) $op - (Opcodes::OP_1 - 1);
}

function encodeOpN($op)
{
    if ($op === 0) {
        return Opcodes::OP_0;
    }

    assert($op === -1 || $op >= 1 && $op <= 16);
    return (int) Opcodes::OP_1 + $op - 1;
}
