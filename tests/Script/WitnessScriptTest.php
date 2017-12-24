<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Exceptions\WitnessScriptException;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\WitnessScript;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class WitnessScriptTest extends AbstractTestCase
{
    /**
     * @return array
     */
    public function getCannotNestVectors()
    {
        return [
            [new WitnessScript(new Script(new Buffer())), "Cannot nest V0 P2WSH scripts."],
            [new P2shScript(new Script(new Buffer())), "Cannot embed a P2SH script in a V0 P2WSH script."],
        ];
    }

    /**
     * @param ScriptInterface $testScript
     * @param string $exceptionMsg
     * @dataProvider getCannotNestVectors
     */
    public function testCannotNestWitnessScripts(ScriptInterface $testScript, string $exceptionMsg)
    {
        $this->expectException(WitnessScriptException::class);
        $this->expectExceptionMessage($exceptionMsg);

        new WitnessScript($testScript);
    }

    public function testNormalScriptHasSameBuffer()
    {
        $script = new Script(new Buffer());
        $witnessScriptHash = $script->getWitnessScriptHash();

        $witnessScript = new WitnessScript($script);
        $this->assertTrue($witnessScript->equals($script));

        $expectedOutputScript = ScriptFactory::scriptPubKey()->p2wsh($witnessScriptHash);
        $this->assertTrue($expectedOutputScript->equals($witnessScript->getOutputScript()));
    }
}
