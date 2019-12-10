<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Script\Consensus\NativeConsensus;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter as I;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;
use const BitWasp\Bitcoin\Script\Interpreter\TAPROOT_LEAF_TAPSCRIPT;

class TaprootTest extends ConsensusTest
{
    public function prepareTestData(): array
    {
        $opcodes = new Opcodes();
        $mapOpNames = $this->calcMapOpNames($opcodes);
        return [
            //[$flags, $returns, $scriptWitness, $scriptSig, $scriptPubKey, $amount, $strTest],
            [0, true, new ScriptWitness(), new Script(), $this->calcScriptFromString($mapOpNames, '0x51 0x20 0xabcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234'), 0, 'should return true when segwit & taproot not active'],
            [I::VERIFY_WITNESS, true, new ScriptWitness(), new Script(), $this->calcScriptFromString($mapOpNames, '0x51 0x20 0xabcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234'), 0, 'should return true when taproot not active'],
            [I::VERIFY_WITNESS|I::VERIFY_TAPROOT, false, new ScriptWitness(), new Script(), $this->calcScriptFromString($mapOpNames, '0x51 0x20 0xabcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234'), 0, 'should return false if scriptWitness empty'],
            [I::VERIFY_WITNESS|I::VERIFY_TAPROOT|I::VERIFY_DISCOURAGE_UPGRADABLE_ANNEX, false, new ScriptWitness(new Buffer(), new Buffer(), new Buffer("\x50")), new Script(), $this->calcScriptFromString($mapOpNames, '0x51 0x20 0xabcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234'), 0, 'VERIFY_DISCOURAGE_UPGRADABLE_ANNEX rejects annex if present'],
            [I::VERIFY_WITNESS|I::VERIFY_TAPROOT, false, new ScriptWitness(new Buffer(), new Buffer(), new Buffer(str_repeat("A", 32))), new Script(), $this->calcScriptFromString($mapOpNames, '0x51 0x20 0xabcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234'), 0, 'control size wrong: (under minimum)'],
            [I::VERIFY_WITNESS|I::VERIFY_TAPROOT, false, new ScriptWitness(new Buffer(), new Buffer(), new Buffer(str_repeat("A", 33+32*128+1))), new Script(), $this->calcScriptFromString($mapOpNames, '0x51 0x20 0xabcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234'), 0, 'control size wrong: (over maximum)'],
            [I::VERIFY_WITNESS|I::VERIFY_TAPROOT, false, new ScriptWitness(new Buffer(), new Buffer(), new Buffer(str_repeat("A", 33+16))), new Script(), $this->calcScriptFromString($mapOpNames, '0x51 0x20 0xabcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234'), 0, 'control size wrong: ((len-33)%32!=0)'],

            [I::VERIFY_WITNESS|I::VERIFY_TAPROOT, true, new ScriptWitness(Buffer::hex('b2ecb4b1957d495d55c72e3deabb3d76ed8d33c2496ffdbdc8577b9605ceec84732f6eb3d90c89931f42d0a76499aa2b493f44e53c2c9d74a3565fd8fecc4bbd')), new Script(), $this->calcScriptFromString($mapOpNames, '0x51 0x20 0x1dcf456c1195e3bd19fe33e52309859253ddc9b95996b0896e36fd3df34eb66a'), 1, 'keypath signature ok', [new TransactionOutput(1, new Script(Buffer::hex("51201dcf456c1195e3bd19fe33e52309859253ddc9b95996b0896e36fd3df34eb66a")))]],
            [I::VERIFY_WITNESS|I::VERIFY_TAPROOT, false, new ScriptWitness(Buffer::hex('11ecb4b1957d495d55c72e3deabb3d76ed8d33c2496ffdbdc8577b9605ceec84732f6eb3d90c89931f42d0a76499aa2b493f44e53c2c9d74a3565fd8fecc4bbd')), new Script(), $this->calcScriptFromString($mapOpNames, '0x51 0x20 0x1dcf456c1195e3bd19fe33e52309859253ddc9b95996b0896e36fd3df34eb66a'), 1, 'keypath signature r first byte wrong', [new TransactionOutput(1, new Script(Buffer::hex("51201dcf456c1195e3bd19fe33e52309859253ddc9b95996b0896e36fd3df34eb66a")))]],
            [I::VERIFY_WITNESS|I::VERIFY_TAPROOT, false, new ScriptWitness(Buffer::hex('b2ecb4b1957d495d55c72e3deabb3d76ed8d33c2496ffdbdc8577b9605ceec84112f6eb3d90c89931f42d0a76499aa2b493f44e53c2c9d74a3565fd8fecc4bbd')), new Script(), $this->calcScriptFromString($mapOpNames, '0x51 0x20 0x1dcf456c1195e3bd19fe33e52309859253ddc9b95996b0896e36fd3df34eb66a'), 1, 'keypath signature s first byte wrong', [new TransactionOutput(1, new Script(Buffer::hex("51201dcf456c1195e3bd19fe33e52309859253ddc9b95996b0896e36fd3df34eb66a")))]],

            [I::VERIFY_WITNESS|I::VERIFY_TAPROOT, true, new ScriptWitness(Buffer::hex('613f2d05989a9b82d407663c96d9f599428f5e4a3cb450d49c8292ebc2a44fc877b90d2273cc54911b5ae466b1ac10b6e7c8c972f31253eba12c14073ef957e901'),
            Buffer::hex('0020b24dbf3e21d269c0da6e5c1da77c8b4041b9ae85aa1747d2db7f9653aa93ed99ba519c'),
            Buffer::hex('c0b24dbf3e21d269c0da6e5c1da77c8b4041b9ae85aa1747d2db7f9653aa93ed99')
            ), new Script(), $this->calcScriptFromString($mapOpNames, '0x51 0x20 0x070af82161cbd04c96cd86e7f8c600a9370092b02c3cef2fbd9dcb639b1b84b7'), 1, 'tapscript 1of1 checksigadd', [new TransactionOutput(1, new Script(Buffer::hex("5120070af82161cbd04c96cd86e7f8c600a9370092b02c3cef2fbd9dcb639b1b84b7")))]],

            [I::VERIFY_WITNESS|I::VERIFY_TAPROOT, false, new ScriptWitness(Buffer::hex('613f2d05989a9b82d407663c96d9f599428f5e4a3cb450d49c8292ebc2a44fc877b90d2273cc54911b5ae466b1ac10b6e7c8c972f31253eba12c14073ef957e901'),
                Buffer::hex('0020b24dbf3e21d269c0da6e5c1da77c8b4041b9ae85aa1747d2db7f9653aa93ed99ba519c'),
                Buffer::hex('c0b24dbf3e21d269c0da6e5c1da77c8b4041b9ae85aa1747d2db7f9653aa93ed99')
            ), new Script(), $this->calcScriptFromString($mapOpNames, '0x51 0x20 0xfffff82161cbd04c96cd86e7f8c600a9370092b02c3cef2fbd9dcb639b1b84b7'), 1, 'first 2 bytes spk wrong', [new TransactionOutput(1, new Script(Buffer::hex("5120070af82161cbd04c96cd86e7f8c600a9370092b02c3cef2fbd9dcb639b1b84b7")))]],

            [I::VERIFY_WITNESS|I::VERIFY_TAPROOT, false, new ScriptWitness(Buffer::hex('613f2d05989a9b82d407663c96d9f599428f5e4a3cb450d49c8292ebc2a44fc877b90d2273cc54911b5ae466b1ac10b6e7c8c972f31253eba12c14073ef957e901'),
                Buffer::hex('ff20b24dbf3e21d269c0da6e5c1da77c8b4041b9ae85aa1747d2db7f9653aa93ed99ba519c'),
                Buffer::hex('c0b24dbf3e21d269c0da6e5c1da77c8b4041b9ae85aa1747d2db7f9653aa93ed99')
            ), new Script(), $this->calcScriptFromString($mapOpNames, '0x51 0x20 0x070af82161cbd04c96cd86e7f8c600a9370092b02c3cef2fbd9dcb639b1b84b7'), 1, '1st byte script element incorrect', [new TransactionOutput(1, new Script(Buffer::hex("5120070af82161cbd04c96cd86e7f8c600a9370092b02c3cef2fbd9dcb639b1b84b7")))]],

            [I::VERIFY_WITNESS|I::VERIFY_TAPROOT, false, new ScriptWitness(Buffer::hex('613f2d05989a9b82d407663c96d9f599428f5e4a3cb450d49c8292ebc2a44fc877b90d2273cc54911b5ae466b1ac10b6e7c8c972f31253eba12c14073ef957e901'),
                Buffer::hex('0020b24dbf3e21d269c0da6e5c1da77c8b4041b9ae85aa1747d2db7f9653aa93ed99ba519c'),
                Buffer::hex('c1b24dbf3e21d269c0da6e5c1da77c8b4041b9ae85aa1747d2db7f9653aa93ed99')
            ), new Script(), $this->calcScriptFromString($mapOpNames, '0x51 0x20 0x070af82161cbd04c96cd86e7f8c600a9370092b02c3cef2fbd9dcb639b1b84b7'), 1, 'is_square_y bit incorrect', [new TransactionOutput(1, new Script(Buffer::hex("5120070af82161cbd04c96cd86e7f8c600a9370092b02c3cef2fbd9dcb639b1b84b7")))]],

            [I::VERIFY_WITNESS|I::VERIFY_TAPROOT, false, new ScriptWitness(Buffer::hex('613f2d05989a9b82d407663c96d9f599428f5e4a3cb450d49c8292ebc2a44fc877b90d2273cc54911b5ae466b1ac10b6e7c8c972f31253eba12c14073ef957e901'),
                Buffer::hex('0020b24dbf3e21d269c0da6e5c1da77c8b4041b9ae85aa1747d2db7f9653aa93ed99ba519c'),
                Buffer::hex('c0ffffbf3e21d269c0da6e5c1da77c8b4041b9ae85aa1747d2db7f9653aa93ed99')
            ), new Script(), $this->calcScriptFromString($mapOpNames, '0x51 0x20 0x070af82161cbd04c96cd86e7f8c600a9370092b02c3cef2fbd9dcb639b1b84b7'), 1, '1st 2 bytes control hash incorrect', [new TransactionOutput(1, new Script(Buffer::hex("5120070af82161cbd04c96cd86e7f8c600a9370092b02c3cef2fbd9dcb639b1b84b7")))]],
        ];
    }

    /**
     * @dataProvider getEcAdapters
     * @throws \Exception
     */
    public function testTweakTestCase(EcAdapterInterface $ec)
    {
        $privFactory = new \BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory($ec);

        $privHex = "f698076154c545857fe7072ecb8df962c965b798b1d2b7640da20db3a6fcdb7d";
        $privKey = $privFactory->fromHexUncompressed($privHex);
        $pub = $privKey->getPublicKey();
        $xonly = $pub->asXOnlyPublicKey();
        $multisig1of1 = ScriptFactory::create()
            ->opcode(Opcodes::OP_0)
            ->data($xonly->getBuffer())
            ->opcode(Opcodes::OP_CHECKSIGADD, Opcodes::OP_1, Opcodes::OP_NUMEQUAL)
            ->getScript();

        $tree = [
            [TAPROOT_LEAF_TAPSCRIPT, $multisig1of1]
        ];
        $ret = \BitWasp\Bitcoin\Script\Taproot\taprootConstruct($xonly, $tree);
        list ($scriptPubKey, $tweak, $scripts, $control) = $ret;

        $this->assertEquals(
            "5120070af82161cbd04c96cd86e7f8c600a9370092b02c3cef2fbd9dcb639b1b84b7",
            $scriptPubKey->getHex()
        );
        $this->assertEquals(
            "85a4787be0566335143ad2d60f72b4db97044236515db7ffd733b8f6e10efe64",
            $tweak->getHex()
        );
        $this->assertEquals(
            $multisig1of1->getHex(),
            $scripts[0]->getHex()
        );
        $this->assertEquals(
            $multisig1of1->getHex(),
            $scripts[0]->getHex()
        );
        $this->assertEquals(
            "c0b24dbf3e21d269c0da6e5c1da77c8b4041b9ae85aa1747d2db7f9653aa93ed99",
            bin2hex($control[0])
        );
    }

    /**
     * @param array $ecAdapterFixtures - array<array<EcAdapterInterface>>
     * @return array - array<array<ConsensusInterface,EcAdapterInterface>>
     */
    public function getConsensusAdapters(array $ecAdapterFixtures): array
    {
        $adapters = [];
        foreach ($ecAdapterFixtures as $ecAdapterFixture) {
            list ($ecAdapter) = $ecAdapterFixture;
            $adapters[] = [new NativeConsensus($ecAdapter)];
        }

        return $adapters;
    }
}