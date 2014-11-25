<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 23/11/14
 * Time: 22:50
 */

namespace Bitcoin;

use Bitcoin\ScriptStackException;

class ScriptStackTest extends \PHPUnit_Framework_TestCase {

    protected $stack;

    public function setUp()
    {
        $this->stack = new ScriptStack;
    }

    /**
     * @expectedException \Bitcoin\ScriptStackException
     */
    public function testPopException()
    {
        $this->stack->pop();
    }

    public function testDump()
    {
        $val = $this->stack->dump();
        $this->assertInternalType('array', $val);
        $this->assertEmpty($val);
    }

    /**
     * @depends testDump
     */
    public function testSet()
    {
        $this->stack->set(0, '41');

        $this->assertInternalType('array', $this->stack->dump());
        $this->assertNotEmpty($this->stack->dump());
        $this->assertTrue(count($this->stack->dump()) == 1);

        // Check specifics of what was set
        $dump = $this->stack->dump();
        $this->assertTrue(isset($dump[0]));
        $this->assertSame($dump[0], '41');

        $this->stack->set(1, '23');
        $this->assertTrue(count($this->stack->dump()) == 2);

        // Check a different value, ie, that the chosen index works
        $dump = $this->stack->dump();
        $this->assertTrue(isset($dump[2]));
        $this->assertSame($dump[2], '23');
    }

    /**
     * @depends testDump
     */
    public function testPush()
    {
        $this->stack->push('41');

        $this->assertInternalType('array', $this->stack->dump());
        $this->assertNotEmpty($this->stack->dump());
        $this->assertTrue(count($this->stack->dump()) == 1);
    }

    /**
     * @depends testPush
     */
    public function testErase()
    {
        $this->stack->push('41');
        $this->stack->erase(-1);
        $this->assertInternalType('array', $this->stack->dump());
        $this->assertEmpty($this->stack->dump());
    }

    /**
     * @expectedException \Bitcoin\ScriptStackException
     */
    public function testEraseException()
    {
        $this->stack->erase(0);
    }

    public function testPop()
    {
        $this->stack
            ->push('41')
            ->push('44')
            ->push('99');

        $this->assertSame($this->stack->pop(), '99');
        $this->assertSame($this->stack->pop(), '44');
        $this->assertSame($this->stack->pop(), '41');
    }

    public function testTop()
    {
        $this->stack
            ->push('41')
            ->push('44')
            ->push('99');

        $this->assertSame($this->stack->top(-1), '99');
        $this->assertSame($this->stack->top(-2), '44');
        $this->assertSame($this->stack->top(-3), '41');
    }
}
 