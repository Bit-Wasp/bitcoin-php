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

    public function testGetOp()
    {
        $op = new OpCodes;
        // Check getRegisteredOpCode returns the right operation
        $expected = 'OP_0';
        $val = $op->getOp(0);

        $this->assertSame($expected, $val);
    }

    /**
     * @depends testGetOp
     * @expectedException \Exception
     */
    public function testGetOpCodeException()
    {
        $op = new OpCodes;
        $op->getOp(3);
    }
}
