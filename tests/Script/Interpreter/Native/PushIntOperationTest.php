<?php

namespace BitWasp\Bitcoin\Tests\Script\Interpreter\Native;

use BitWasp\Bitcoin\Script\Interpreter\Operation\PushIntOperation;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptStack;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class PushIntOperationTest extends AbstractTestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Opcode not found
     */
    public function testOpCodeNotFound()
    {
        // 101 is not in the right range, should fail.
        $operation = new PushIntOperation(new Opcodes());
        $operation->op(101, new ScriptStack());
    }
}
