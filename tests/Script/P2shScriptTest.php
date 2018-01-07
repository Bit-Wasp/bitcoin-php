<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Exceptions\P2shScriptException;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\WitnessScript;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class P2shScriptTest extends AbstractTestCase
{
    /**
     * @return array
     */
    public function getCannotNestVectors()
    {
        return [
            [new P2shScript(new Script(new Buffer())), "Cannot nest P2SH scripts."],
        ];
    }

    /**
     * @param ScriptInterface $testScript
     * @param string $exceptionMsg
     * @dataProvider getCannotNestVectors
     */
    public function testCannotNestWitnessScripts(ScriptInterface $testScript, string $exceptionMsg)
    {
        $this->expectException(P2shScriptException::class);
        $this->expectExceptionMessage($exceptionMsg);

        new P2shScript($testScript);
    }

    public function testP2WSHForP2SHIsForbidden()
    {
        $this->expectException(P2shScriptException::class);
        $this->expectExceptionMessage("Cannot compute witness-script-hash for a P2shScript");

        (new P2shScript(new Script(new Buffer())))->getWitnessScriptHash();
    }

    public function testNormalScriptHasSameBuffer()
    {
        $script = ScriptFactory::sequence([Opcodes::OP_0]);
        $p2shScript = new P2shScript($script);
        $this->assertTrue($p2shScript->equals($script));

        $expectedP2sh = ScriptFactory::scriptPubKey()->p2sh($script->getScriptHash());
        $this->assertTrue($p2shScript->getOutputScript()->equals($expectedP2sh));

        $expectedAddress = (new ScriptHashAddress($script->getScriptHash()))->getAddress();
        $this->assertEquals($expectedAddress, $p2shScript->getAddress()->getAddress());
    }

    public function testConsumesWitnessScriptOutputScript()
    {
        $script = ScriptFactory::sequence([Opcodes::OP_0]);

        $witnessScript = new WitnessScript($script);
        $p2shScript = new P2shScript($witnessScript);
        $this->assertTrue($p2shScript->equals($witnessScript->getOutputScript()));

        // be absolutely sure
        $expectedP2sh = ScriptFactory::scriptPubKey()->p2sh($witnessScript->getWitnessProgram()->getScript()->getScriptHash());
        $this->assertTrue($p2shScript->getOutputScript()->equals($expectedP2sh));
    }

    public function getOutputScriptAndAddressVectors()
    {
        $script = ScriptFactory::sequence([Opcodes::OP_0]);
        $scriptHash = $script->getScriptHash();
        $witnessScript = new WitnessScript($script);
        $wpScriptHash = $witnessScript->getWitnessProgram()->getScript()->getScriptHash();
        return [
            [$script, $script, $scriptHash],
            [$witnessScript, $witnessScript->getOutputScript(), $wpScriptHash],
        ];
    }

    /**
     * @param ScriptInterface $script
     * @param ScriptInterface $expectedP2SH
     * @param BufferInterface $expectedScriptHash
     * @dataProvider getOutputScriptAndAddressVectors
     */
    public function testOutputScriptAndAddress(ScriptInterface $script, ScriptInterface $expectedP2SH, BufferInterface $expectedScriptHash)
    {
        $p2shScript = new P2shScript($script);
        $this->assertTrue($p2shScript->equals($expectedP2SH));
        $this->assertTrue($expectedScriptHash->equals($p2shScript->getScriptHash()));

        $expectedOutputScript = ScriptFactory::scriptPubKey()->p2sh($expectedScriptHash);
        $outputScript = $p2shScript->getOutputScript();
        $this->assertTrue($outputScript->equals($expectedOutputScript));

        $expectedAddress = (new ScriptHashAddress($expectedScriptHash))->getAddress();
        $address = $p2shScript->getAddress()->getAddress();
        $this->assertEquals($expectedAddress, $address);
    }
}
