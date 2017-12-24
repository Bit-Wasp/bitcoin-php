<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script\Branch;

use BitWasp\Bitcoin\Script\Path\LogicOpNode;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class LogicOpNodeTest extends AbstractTestCase
{
    public function testGetChildWithNoneThrowsError()
    {
        $logicNode = new LogicOpNode();
        $this->assertFalse($logicNode->hasChildren());
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Child not found");

        $logicNode->getChild(0);
    }

    public function testNodeWontSplitTwice()
    {
        $threw = false;
        try {
            $logicNode = new LogicOpNode();
            $this->assertFalse($logicNode->hasChildren());
            $logicNode->split();
        } catch (\Exception $e) {
            $threw = true;
        }

        $this->assertFalse($threw, "control split should not fail");
        $this->assertTrue($logicNode->hasChildren());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Sanity check - don't split twice");

        $logicNode->split();
    }
}
