<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script\Branch;

use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\Path\LogicOpNode;
use BitWasp\Bitcoin\Script\Path\ParsedScript;
use BitWasp\Bitcoin\Script\Path\ScriptBranch;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class ParsedScriptTest extends AbstractTestCase
{
    public function testRequiresRootLogicOpNode()
    {
        $root = new LogicOpNode();
        list ($child, ) = $root->split();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("LogicOpNode was not for root");

        new ParsedScript(new Script(new Buffer()), $child, []);
    }

    public function testGetBranchByPathWroks()
    {
        $script = new Script(new Buffer("\x01\x01"));
        $onlyPath = [];
        $onlyBranch = new ScriptBranch($script, $onlyPath, [
            [new Operation(1, new Buffer("\x01"))]
        ]);

        $ps = new ParsedScript($script, new LogicOpNode(), [
            $onlyBranch,
        ]);

        $branch = $ps->getBranchByPath($onlyPath);
        $this->assertSame($onlyBranch, $branch);
    }

    public function testGetBranchByPathFailsForUnknownPath()
    {
        $script = new Script(new Buffer("\x01\x01"));
        $onlyPath = [];
        $onlyBranch = new ScriptBranch($script, $onlyPath, [
            [new Operation(1, new Buffer("\x01"))]
        ]);

        $ps = new ParsedScript($script, new LogicOpNode(), [
            $onlyBranch,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unknown logical pathway");

        $ps->getBranchByPath([true, false, false, true]);
    }

    public function testRejectsDuplicatePaths()
    {
        $script = new Script(new Buffer("\x0101"));
        $branch = new ScriptBranch($script, [], [
            [new Operation(1, new Buffer("\x01"))]
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Duplicate logical pathway, invalid ScriptBranch found");

        new ParsedScript($script, new LogicOpNode(), [$branch, $branch]);
    }
}
