<?php

namespace BitWasp\Bitcoin\Tests\Script\Branch;


use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Path\BranchInterpreter;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class BranchInterpreterTest extends AbstractTestCase
{
    public function testDetectsUnexpectedEndIf()
    {
        $script = ScriptFactory::sequence([
            Opcodes::OP_ENDIF,
        ]);

        $bi = new BranchInterpreter();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unexpected ENDIF, current scope had no parent");

        $bi->getAstForLogicalOps($script);
    }

    public function testDetectsUnexpectedElse()
    {
        $script = ScriptFactory::sequence([
            Opcodes::OP_ELSE,
        ]);

        $bi = new BranchInterpreter();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unexpected ELSE, current scope had no parent");

        $bi->getAstForLogicalOps($script);
    }


    public function testDetectsUnbalancedIfBranch()
    {
        $script = ScriptFactory::sequence([
            Opcodes::OP_1, Opcodes::OP_IF, Opcodes::OP_DROP
        ]);

        $bi = new BranchInterpreter();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unbalanced conditional - vfStack not empty at script termination");

        $bi->getAstForLogicalOps($script);
    }
}
