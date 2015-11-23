<?php

namespace BitWasp\Bitcoin\Tests\Script\Interpreter;

use BitWasp\Bitcoin\Script\Interpreter\Stack;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class StackTest extends AbstractTestCase
{
    /**
     * @expectedException \RuntimeException
     */
    public function testPopException()
    {
        $stack = new Stack;
        $stack->pop();
    }

    public function testAdd()
    {
        $value1 = Buffer::hex('65');
        $value2 = Buffer::hex('41');

        $stack = new Stack;
        $stack->add(0, $value2);

        $this->assertTrue(count($stack) == 1);

        // Check specifics of what was set
        $this->assertTrue(isset($stack[-1]));
        $this->assertSame($stack[-1], $value2);

        $stack->add(1, $value1);
        $this->assertTrue(count($stack) == 2);

        // Check a different value2, ie, that the chosen index works
        $this->assertTrue(isset($stack[-2]));
        $this->assertSame($stack[-1], $value1);
        $this->assertSame($stack[-2], $value2);
    }

    /**
     */
    public function testPush()
    {
        $stack = new Stack;
        $stack->push(Buffer::hex('41'));

        $this->assertTrue(count($stack) == 1);
    }

    public function testErase()
    {
        $stack = new Stack;
        $stack->push(Buffer::hex('41'));
        $this->assertTrue(count($stack) == 1);

        unset($stack[-1]);
        $this->assertEmpty($stack);
        $this->assertTrue(count($stack) == 0);
    }

    /**
     * @expectedException \Exception
     */
    public function testEraseException()
    {
        $stack = new Stack;
        unset($stack[0]);
    }

    public function testPop()
    {
        $list =  ['41', '44', '99'];
        $arr = array_map(function ($v) {
            return Buffer::hex($v);
        }, $list);

        $stack = new Stack;
        foreach ($arr as $p) {
            $stack->push($p);
        }

        $ePop = array_reverse($list);
        foreach ($arr as $c => $p) {
            $this->assertSame($stack->pop()->getHex(), $ePop[$c]);
        }
    }

    public function testRelativeAccess()
    {
        $stack = new Stack();
        $list =  ['41', '44', '99'];
        array_walk($list, function ($v) use ($stack) {
            $stack->push(Buffer::hex($v));
        });

        $this->assertSame($stack[-1]->getHex(), '99');
        $this->assertSame($stack[-2]->getHex(), '44');
        $this->assertSame($stack[-3]->getHex(), '41');
    }

    public function testInsert()
    {
        $stack = new Stack();
        $list =  ['41', '44', '99'];
        array_walk($list, function ($v) use ($stack) {
            $stack->push(Buffer::hex($v));
        });

        $stack->add(0, Buffer::hex('de'));

        $this->assertEquals(4, count($stack));
        $this->assertSame('99', $stack[-1]->getHex());
        $this->assertSame('44', $stack[-2]->getHex());
        $this->assertSame('41', $stack[-3]->getHex());
        $this->assertSame('de', $stack[-4]->getHex());

        $stack->add(2, Buffer::hex('df'));
        $this->assertEquals('df', $stack[-3]->getHex());

    }

    public function testCount()
    {
        $stack = new Stack;
        $this->assertEquals(0, count($stack));

        $stack->push(Buffer::hex('41'));
        $stack->push(Buffer::hex('44'));

        $this->assertEquals(2, count($stack));
        $stack->push(Buffer::hex('fa'));

        $this->assertEquals(3, count($stack));
    }

    public function testSwap()
    {
        $stack = new Stack();
        $stack->push(Buffer::hex('00'));
        $stack->push(Buffer::hex('11'));

        $this->assertEquals('11', $stack[-1]->getHex());
        $this->assertEquals('00', $stack[-2]->getHex());

        $stack->swap(-1, -2);
        $this->assertEquals('00', $stack[-1]->getHex());
        $this->assertEquals('11', $stack[-2]->getHex());

        $stack->push(Buffer::hex('22'));
        $this->assertEquals('22', $stack[-1]->getHex());
        $this->assertEquals('00', $stack[-2]->getHex());
        $this->assertEquals('11', $stack[-3]->getHex());

        $stack->swap(-1, -2);
        $this->assertEquals('00', $stack[-1]->getHex());
        $this->assertEquals('22', $stack[-2]->getHex());
        $this->assertEquals('11', $stack[-3]->getHex());

    }
}
