<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Key\PrivateKeyFactory;
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
                false,
                0
            ],
            [
                new Script(),
                true,
                0
            ],
            [
                $s1,
                true,
                2
            ],
            [
                $s2,
                true,
                3
            ],
            [
                $s2,
                false,
                21
            ]
        ];
    }

    public function testP2sh()
    {
        $innerScript = ScriptFactory::create()
            ->op('OP_1')
            ->push(new Buffer())
            ->push(new Buffer())
            ->op('OP_2')
            ->op('OP_CHECKMULTISIG')
            ->getScript();

        $innerBuf = $innerScript->getBuffer();

        $p2sh = ScriptFactory::scriptPubKey()->payToScriptHash($innerScript);
        $scriptSig = ScriptFactory::create()->op('OP_0')->push($innerBuf)->getScript();
        $count = $p2sh->countP2shSigOps($scriptSig);

        $this->assertEquals(2, $count);

    }

    public function testMultisig()
    {
        $pk = [];
        for ($i = 0; $i < 3; $i++) {
            $pk[] = PrivateKeyFactory::create()->getPublicKey();
        }

        $p2shScript = ScriptFactory::scriptPubKey()->multisig(1, $pk);
        $this->assertEquals(3, $p2shScript->countSigOps(true));
        $this->assertEquals(20, $p2shScript->countSigOps(false));

        $scriptPubKey = ScriptFactory::scriptPubKey()->payToScriptHash($p2shScript);
        $this->assertEquals(0, $scriptPubKey->countSigOps(true));
        $this->assertEquals(0, $scriptPubKey->countSigOps(false));

        $scriptSig = ScriptFactory::create()
            ->op('OP_1')
            ->push(new Buffer())
            ->push(new Buffer())
            ->push($p2shScript->getBuffer())
            ->getScript();

        $this->assertEquals(3, $scriptPubKey->countP2shSigOps($scriptSig));
    }

    /**
     * @param Script $script
     * @param $fAccurate
     * @param $eSigOpCount
     * @dataProvider getCountTestVectors
     */
    public function testSigOpCount(Script $script, $fAccurate, $eSigOpCount)
    {
        $this->assertEquals($eSigOpCount, $script->countSigOps($fAccurate));
    }

    public function testWhenNotP2sh()
    {
        $p2pkh = ScriptFactory::create()->op('OP_DUP')->op('OP_HASH160')->push(new Buffer())->op('OP_EQUALVERIFY')->op('OP_CHECKSIG')->getScript();
        $empty = new Script();
        $this->assertEquals(1, $p2pkh->countP2shSigOps($empty));

        $p2shPubKey = ScriptFactory::scriptPubKey()->payToScriptHash($empty);
        $this->assertEquals(0, $p2shPubKey->countP2shSigOps($empty));

        $p2shScript = ScriptFactory::create()->op('OP_HASH160')->getScript();
        $p2shPubKey1 = ScriptFactory::scriptPubKey()->payToScriptHash($p2shScript);
        $this->assertEquals(0, $p2shPubKey1->countP2shSigOps($p2shScript));
    }
}
