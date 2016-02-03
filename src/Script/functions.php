<?php

function decodeOpN($op)
{
    if ($op === \BitWasp\Bitcoin\Script\Opcodes::OP_0) {
        return 0;
    }

    assert($op >= \BitWasp\Bitcoin\Script\Opcodes::OP_1 && $op <= \BitWasp\Bitcoin\Script\Opcodes::OP_16);
    return $op - (\BitWasp\Bitcoin\Script\Opcodes::OP_1 - 1);
}

function encodeOpN($op)
{
    if ($op === 0) {
        return \BitWasp\Bitcoin\Script\Opcodes::OP_0;
    }

    assert($op >= 1 && $op <= 16);
    return \BitWasp\Bitcoin\Script\Opcodes::OP_1 + $op - 1;
}
