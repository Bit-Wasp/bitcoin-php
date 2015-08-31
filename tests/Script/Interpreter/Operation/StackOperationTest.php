<?php

namespace BitWasp\Bitcoin\Tests\Script\Interpreter\Operation;

use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Interpreter\Operation\StackOperation;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptStack;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class StackOperationTest extends AbstractTestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Opcode not found
     */
    public function testOpCodeNotFound()
    {
        // 101 is not in the right range, should fail.
        $operation = new StackOperation(new Opcodes(), new Math(), new Flags(0), function () {
        });
        $operation->op(101, new ScriptStack(), new ScriptStack(), false);
    }
}
