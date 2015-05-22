<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Script\Opcodes;

class OpcodesTest extends \PHPUnit_Framework_TestCase
{
    public function testGetOpByName()
    {
        $op = new OpCodes;
        $expected = 0;
        $val = $op->getOpByName('OP_0');
        $this->assertSame($expected, $val);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Opcode by that name not found
     */
    public function testGetOpByNameFail()
    {
        $op = new Opcodes();
        $op->opNameExists('OP_DEADBEEF');
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
     *
     */
    public function testIsOp()
    {
        $op = new Opcodes();
        $this->assertTrue($op->isOp(0xae, 'OP_CHECKMULTISIG'));
        $this->assertFalse($op->isOp(0xad, 'OP_CHECKMULTISIG'));
    }
}
