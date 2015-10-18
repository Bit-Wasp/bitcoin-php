<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class ScriptCountSigOpsTest extends AbstractTestCase
{
    public function getCountTestVectors()
    {
        $s1 = ScriptFactory::create()->op('OP_1')->push(new Buffer())->push(new Buffer())->op('OP_2')->op('OP_CHECKMULTISIG')->getScript();
        $s2 = ScriptFactory::create($s1->getBuffer())->op('OP_IF')->op('OP_CHECKSIG')->op('OP_ENDIF')->getScript();
        return [
            [
                new Script(),
                0
            ],
            [
                $s1,
                2
            ],
            [
                $s2,
                3
            ]
        ];
    }

    /**
     * @param Script $script
     * @param $eSigOpCount
     * @dataProvider getCountTestVectors
     */
    public function testEmptyScriptHashZero(Script $script, $eSigOpCount)
    {
        $this->assertEquals($eSigOpCount, $script->countSigOps());
    }
}
