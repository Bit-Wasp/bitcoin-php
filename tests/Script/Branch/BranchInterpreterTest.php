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

    public function testDetectsReservedOpcodes()
    {
        $script = ScriptFactory::sequence([
            Opcodes::OP_XOR,
        ]);

        $bi = new BranchInterpreter();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Disabled Opcode");

        $bi->evaluateUsingStack($script, []);
    }

    public function testDetectsIfEvaluationWithoutStackValue()
    {
        $script = ScriptFactory::sequence([
            Opcodes::OP_IF, Opcodes::OP_ENDIF,
        ]);
        $path = [];
        $bi = new BranchInterpreter();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unbalanced conditional at OP_IF - not included in logicalPath");

        $bi->evaluateUsingStack($script, $path);
    }

    public function testDetectsNotIfEvaluationWithoutStackValue()
    {
        $script = ScriptFactory::sequence([
            Opcodes::OP_NOTIF, Opcodes::OP_ENDIF,
        ]);
        $path = [];
        $bi = new BranchInterpreter();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unbalanced conditional at OP_NOTIF - not included in logicalPath");

        $bi->evaluateUsingStack($script, $path);
    }

    public function testDetectsUnbalancedAtEndif()
    {
        $script = ScriptFactory::sequence([
            Opcodes::OP_ENDIF,
        ]);
        $path = [];
        $bi = new BranchInterpreter();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unbalanced conditional at OP_ENDIF");

        $bi->evaluateUsingStack($script, $path);
    }

    public function testDetectsUnbalancedAtElse()
    {
        $script = ScriptFactory::sequence([
            Opcodes::OP_ELSE,
        ]);
        $path = [];
        $bi = new BranchInterpreter();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unbalanced conditional at OP_ELSE");

        $bi->evaluateUsingStack($script, $path);
    }

    public function testDetectsUnbalancedUnfinishedScript()
    {
        $script = ScriptFactory::sequence([
            Opcodes::OP_IF,
        ]);
        $path = [true];
        $bi = new BranchInterpreter();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unbalanced conditional at script end");

        $bi->evaluateUsingStack($script, $path);
    }

    public function testDetectsUnbalancedPath()
    {
        $script = ScriptFactory::sequence([
            Opcodes::OP_IF, Opcodes::OP_ENDIF,
        ]);
        $path = [true, true];
        $bi = new BranchInterpreter();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Values remaining after script execution - invalid branch data");

        $bi->evaluateUsingStack($script, $path);
    }
}
