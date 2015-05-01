<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Script\ScriptStack;

class ScriptStackTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\ScriptStackException
     */
    public function testPopException()
    {
        $stack = new ScriptStack;
        $stack->pop();
    }

    public function testDump()
    {
        $stack = new ScriptStack;
        $val = $stack->dump();
        $this->assertInternalType('array', $val);
        $this->assertEmpty($val);
    }

    public function testSet()
    {
        $stack = new ScriptStack;
        $stack->set(0, '41');

        $this->assertInternalType('array', $stack->dump());
        $this->assertNotEmpty($stack->dump());
        $this->assertTrue(count($stack->dump()) == 1);

        // Check specifics of what was set
        $dump = $stack->dump();
        $this->assertTrue(isset($dump[0]));
        $this->assertSame($dump[0], '41');

        $stack->set(1, '23');
        $this->assertTrue(count($stack->dump()) == 2);

        // Check a different value, ie, that the chosen index works
        $dump = $stack->dump();
        $this->assertTrue(isset($dump[2]));
        $this->assertSame($dump[2], '23');
    }

    /**
     * @depends testDump
     */
    public function testPush()
    {
        $stack = new ScriptStack;
        $stack->push('41');

        $this->assertInternalType('array', $stack->dump());
        $this->assertNotEmpty($stack->dump());
        $this->assertTrue(count($stack->dump()) == 1);
    }

    /**
     * @depends testPush
     */
    public function testErase()
    {
        $stack = new ScriptStack;
        $stack->push('41');
        $stack->erase(-1);
        $this->assertInternalType('array', $stack->dump());
        $this->assertEmpty($stack->dump());
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\ScriptStackException
     */
    public function testEraseException()
    {
        $stack = new ScriptStack;
        $stack->erase(0);
    }

    public function testPop()
    {
        $stack = new ScriptStack;
        $stack
            ->push('41')
            ->push('44')
            ->push('99');

        $this->assertSame($stack->pop(), '99');
        $this->assertSame($stack->pop(), '44');
        $this->assertSame($stack->pop(), '41');
    }

    public function testTop()
    {
        $stack = new ScriptStack;
        $stack
            ->push('41')
            ->push('44')
            ->push('99');

        $this->assertSame($stack->top(-1), '99');
        $this->assertSame($stack->top(-2), '44');
        $this->assertSame($stack->top(-3), '41');
    }
}
