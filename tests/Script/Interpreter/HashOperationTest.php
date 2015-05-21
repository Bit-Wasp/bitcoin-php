<?php

namespace BitWasp\Bitcoin\Tests\Script\Interpreter;


use BitWasp\Bitcoin\Script\Interpreter\HashOperation;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptStack;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class HashOperationTest extends AbstractTestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Opcode not found
     */
    public function testOpCodeNotFound()
    {
        // 101 is not in the right range, should fail.
        $operation = new HashOperation(new Opcodes());
        $stack = new ScriptStack();
        $stack->push(new Buffer());
        $operation->op(101, $stack);
    }
}