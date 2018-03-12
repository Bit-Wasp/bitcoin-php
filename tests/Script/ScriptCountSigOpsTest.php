<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class ScriptCountSigOpsTest extends AbstractTestCase
{
    public function getCountTestVectors()
    {
        $s1 = ScriptFactory::create()->opcode(Opcodes::OP_1)->push(new Buffer())->push(new Buffer())->opcode(Opcodes::OP_2, Opcodes::OP_CHECKMULTISIG)->getScript();
        $s2 = ScriptFactory::create($s1->getBuffer())->opcode(Opcodes::OP_IF, Opcodes::OP_CHECKSIG, Opcodes::OP_ENDIF)->getScript();

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
            ->opcode(Opcodes::OP_1)
            ->data(new Buffer(), new Buffer())
            ->opcode(Opcodes::OP_2)
            ->opcode(Opcodes::OP_CHECKMULTISIG)
            ->getScript();

        $innerBuf = $innerScript->getBuffer();

        $p2sh = ScriptFactory::scriptPubKey()->payToScriptHash(Hash::sha256ripe160($innerScript->getBuffer()));
        $scriptSig = ScriptFactory::create()->opcode(Opcodes::OP_0)->push($innerBuf)->getScript();
        $count = $p2sh->countP2shSigOps($scriptSig);

        $this->assertEquals(2, $count);
    }

    public function testMultisig()
    {
        $pubKeyFactory = new PublicKeyFactory();
        $pk = [];
        $pk[] = $pubKeyFactory->fromHex('045b81f0017e2091e2edcd5eecf10d5bdd120a5514cb3ee65b8447ec18bfc4575c6d5bf415e54e03b1067934a0f0ba76b01c6b9ab227142ee1d543764b69d901e0');
        $pk[] = $pk[0]->tweakAdd(gmp_init(1));
        $pk[] = $pk[0]->tweakAdd(gmp_init(2));

        $p2shScript = ScriptFactory::scriptPubKey()->multisig(1, $pk);
        $this->assertEquals(3, $p2shScript->countSigOps(true));
        $this->assertEquals(20, $p2shScript->countSigOps(false));

        $scriptPubKey = ScriptFactory::scriptPubKey()->payToScriptHash(Hash::sha256ripe160($p2shScript->getBuffer()));
        $this->assertEquals(0, $scriptPubKey->countSigOps(true));
        $this->assertEquals(0, $scriptPubKey->countSigOps(false));

        $scriptSig = ScriptFactory::create()
            ->opcode(Opcodes::OP_1)
            ->data(new Buffer(), new Buffer())
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
        $p2pkh = ScriptFactory::create()
            ->opcode(Opcodes::OP_DUP, Opcodes::OP_HASH160)
            ->push(new Buffer())
            ->opcode(Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG)->getScript();

        $empty = new Script();
        $this->assertEquals(1, $p2pkh->countP2shSigOps($empty));

        $p2shPubKey = ScriptFactory::scriptPubKey()->payToScriptHash(Hash::sha256ripe160($empty->getBuffer()));
        $this->assertEquals(0, $p2shPubKey->countP2shSigOps($empty));

        $p2shScript = ScriptFactory::create()->opcode(Opcodes::OP_HASH160)->getScript();
        $p2shPubKey1 = ScriptFactory::scriptPubKey()->payToScriptHash(Hash::sha256ripe160($p2shScript->getBuffer()));
        $this->assertEquals(0, $p2shPubKey1->countP2shSigOps($p2shScript));
    }
}
