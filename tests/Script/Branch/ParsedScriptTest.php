<?php

namespace BitWasp\Bitcoin\Tests\Script\Branch;

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

    public function testRejectsDuplicatePaths()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Duplicate logical pathway, invalid ScriptBranch found");

        $script = new Script(new Buffer());
        new ParsedScript($script, new LogicOpNode(), [
            new ScriptBranch($script, [], []),
            new ScriptBranch($script, [], []),
        ]);
    }
}
