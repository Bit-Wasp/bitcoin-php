<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Script\ScriptInterpreterFlags;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class ScriptInterpreterFlagsTest extends AbstractTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage CheckDisabledOpcodes must be a boolean
     */
    public function testFailsOnNonBoolean()
    {
        new ScriptInterpreterFlags(0, 'shouldfail');
    }

    public function testStaticProperties()
    {
        $flags = new ScriptInterpreterFlags(0, false);
        $this->assertEquals(520, $flags->getMaxElementSize());
        $this->assertEquals(10000, $flags->getMaxBytes());
        $this->assertFalse($flags->checkDisabledOpcodes());
    }

    public function testBasicChecks()
    {
        $flags = new ScriptInterpreterFlags(0, false);
        $this->assertFalse($flags->checkDisabledOpcodes());
        $this->assertFalse($flags->checkFlags(ScriptInterpreterFlags::VERIFY_P2SH));
        $this->assertFalse($flags->checkFlags(ScriptInterpreterFlags::VERIFY_STRICTENC));

        $flags = new ScriptInterpreterFlags(ScriptInterpreterFlags::VERIFY_STRICTENC, false);
        $this->assertFalse($flags->checkFlags(ScriptInterpreterFlags::VERIFY_P2SH));
        $this->assertTrue($flags->checkFlags(ScriptInterpreterFlags::VERIFY_STRICTENC));
        $this->assertFalse($flags->checkFlags(ScriptInterpreterFlags::VERIFY_DERSIG));

        $flags = new ScriptInterpreterFlags(ScriptInterpreterFlags::VERIFY_MINIMALDATA);
        $this->assertFalse($flags->checkFlags(ScriptInterpreterFlags::VERIFY_SIGPUSHONLY));
        $this->assertTrue($flags->checkFlags(ScriptInterpreterFlags::VERIFY_MINIMALDATA));
        $this->assertFalse($flags->checkFlags(ScriptInterpreterFlags::VERIFY_DISCOURAGE_UPGRADABLE_NOPS));
    }

    public function testDefaults()
    {
        $flags = ScriptInterpreterFlags::defaults();
        $this->assertTrue($flags->checkFlags(ScriptInterpreterFlags::VERIFY_P2SH));
        $this->assertTrue($flags->checkFlags(ScriptInterpreterFlags::VERIFY_STRICTENC));
        $this->assertTrue($flags->checkFlags(ScriptInterpreterFlags::VERIFY_DERSIG));
        $this->assertTrue($flags->checkFlags(ScriptInterpreterFlags::VERIFY_LOW_S));
        $this->assertTrue($flags->checkFlags(ScriptInterpreterFlags::VERIFY_NULL_DUMMY));
        $this->assertTrue($flags->checkFlags(ScriptInterpreterFlags::VERIFY_SIGPUSHONLY));
        $this->assertTrue($flags->checkFlags(ScriptInterpreterFlags::VERIFY_DISCOURAGE_UPGRADABLE_NOPS));
        $this->assertTrue($flags->checkFlags(ScriptInterpreterFlags::VERIFY_CLEAN_STACK));
        $this->assertTrue($flags->checkDisabledOpcodes());
    }
}
