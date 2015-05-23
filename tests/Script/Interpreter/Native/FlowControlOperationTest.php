<?php

namespace BitWasp\Bitcoin\Tests\Script\Interpreter\Native;

use BitWasp\Bitcoin\Script\Interpreter\Native\FlowControlOperation;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptStack;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class FlowControlOperationTest extends AbstractTestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Opcode not found
     */
    public function testOpCodeNotFound()
    {
        // 101 is not in the right range, should fail.
        $operation = new FlowControlOperation(new Opcodes(), function () {
        });
        $operation->op(101, new ScriptStack(), new ScriptStack(), false);
    }
}
