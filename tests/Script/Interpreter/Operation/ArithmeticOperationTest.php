<?php

namespace BitWasp\Bitcoin\Tests\Script\Interpreter\Operation;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Interpreter\Operation\ArithmeticOperation;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptStack;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class ArithmeticOperationTest extends AbstractTestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Opcode not found
     */
    public function testOpCodeNotFound()
    {
        // 101 is not in the right range, should fail.
        $operation = new ArithmeticOperation(new Opcodes(), new Math(), function () {
        }, new Buffer(), new Buffer());
        $stack = new ScriptStack();
        $stack->push(new Buffer());
        $operation->op(101, $stack);
    }
}
