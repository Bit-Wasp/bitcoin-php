<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Script\Opcodes;

class OpcodesTest extends \PHPUnit_Framework_TestCase
{
    public function testGetOpByName()
    {
        $op = new OpCodes;
        $expected = 0;
        $lookupOpName = 'OP_0';
        $val = $op->getOpByName('OP_0');
        $this->assertSame($expected, $val);
        $this->assertTrue(isset($op[Opcodes::OP_0]));
        $this->assertSame($lookupOpName, $op[Opcodes::OP_0]);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Opcode by that name not found
     */
    public function testGetOpByNameFail()
    {
        $op = new Opcodes();
        $op->getOpByName('OP_DEADBEEF');
    }

    public function testGetOp()
    {
        $op = new OpCodes;
        // Check getRegisteredOpCode returns the right operation
        $expected = 'OP_0';
        $val = $op->getOp(0);

        $this->assertSame($expected, $val);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Opcode not found
     */
    public function testGetOpCodeException()
    {
        $op = new OpCodes;
        $op->getOp(3);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testNoWriteSet()
    {
        $op = new Opcodes();
        $op[1] = 2;
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testNoWriteUnSet()
    {
        $op = new Opcodes();
        unset($op[Opcodes::OP_1]);
    }
}
