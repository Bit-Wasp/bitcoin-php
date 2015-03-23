<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Script\Opcodes;

class OpcodesTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Opcodes
     */
    public $op;

    public function setUp()
    {
        $this->op = new OpCodes;
    }

    public function testGetOpByName()
    {
        $expected = 0;
        $val = $this->op->getOpByName('OP_0');
        $this->assertSame($expected, $val);
    }

    public function testGetOp()
    {
        // Check getRegisteredOpCode returns the right operation
        $expected = 'OP_0';
        $val = $this->op->getOp(0);

        $this->assertSame($expected, $val);
    }

    /**
     * @depends testGetOp
     * @expectedException \Exception
     */
    public function testGetOpCodeException()
    {
        $val = $this->op->getOp(3);
    }

}
