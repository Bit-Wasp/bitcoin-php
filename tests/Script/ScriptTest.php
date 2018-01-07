<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;

class ScriptTest extends AbstractTestCase
{
    public function testGetOpCodes()
    {
        $script = new Script();
        $opCodes = $script->getOpCodes();
        $this->assertInstanceOf(Opcodes::class, $opCodes);
    }

    public function testPushdata4()
    {
        // Create a buffer with a fixed length, in the pushdata4 range.
        $eLen = 65536;
        $buffer = new Buffer('', $eLen);

        $script = ScriptFactory::create()->push($buffer)->getScript();

        $result = $script->getScriptParser()->decode();
        $this->assertEquals(Opcodes::OP_PUSHDATA4, $result[0]->getOp());
        $this->assertEquals($eLen, $result[0]->getDataSize());
        $this->assertEquals($buffer->getBinary(), $result[0]->getData()->getBinary());
    }

    public function testDefaultSerializeBinary()
    {
        $script = new Script();
        $val = $script->getBuffer()->getBinary();
        $this->assertEmpty($val);
    }

    public function testDefaultSerializeHex()
    {
        $script = new Script();
        $val = $script->getBuffer()->getHex();
        $this->assertEmpty($val);
    }

    public function testSerializeBinary()
    {
        $script = new Script();
        $val = $script->getBuffer()->getBinary();
        $this->assertEmpty($val);
    }

    public function testSerializeHex()
    {
        $script = new Script();
        $val = $script->getBuffer()->getHex();
        $this->assertEmpty($val);
    }

    public function testGetScript()
    {
        $hex = '00483045022057e65d83fb50768f310953300cdf09e8c551a716de81eb9e9bea2b055cffce53022100830c1636104d5ba704ef92849db0415182c364278b7f2a53097b65beb1c755c001483045022100b16c0cf3d6e16a9f9a2559c0043c083e46a8557df1f22755e396b94b08e8624202203b6a9927ceb70eda3e71f584dffe108ef0fcc5040538de45f85c1645b115168601473044022006135422817bd9f8cd24004c9797114838944a7594b6d9d7da043c93700c58bf0220009c226d944fc1d2c4a29d9b687aab04f2f65f9688c468594a0747067fa717800149304602210093f6c1402fdefd71e890168f8a2eb34ff18b50a9babdfd1b4a69c8895b10a9bb022100b7fea02dbc6391ac6403f628afe576c2e8b42f7d31c7c38d959766b45e114f6e01483045022100f6d4fa96d2d221cc0368b0da1fafc889c5212e1a65a5d7b5937d374993568bb002206fc78de031d1cd34b203abedac0ef628ad6c863a0c505533da12cf34bf74fdba01483045022100b52f4d6f1e69554f15b9e02be1a3f03d96943c2aa21544047d9156b91a2eace5022023b41bef3725b1a6cab9c509b95e3a2f839536325597a2359ea0c14786adf2a8014ccf5621025d951ab5a9c3656aa25b4facf7b9824ca3cca7f9eaf3b84551d3aef8b0803a5721027b7eb1910184738f54b00ee7c5f695598d0f21b8ea87bface1e9d901fa5193802102e8537cc8081358b9bbcbd221da7f10ec167fbadcb03b8ff2980c8a78aca076712102f2d0f1996cf932b766032ea1da0051d8e7688516eb005b9ffd6acfbf032627c321030bd27f6a978bc03748b301e20531dd76f27ddcc25e51c09e65a6e4dafa8abbaf21037bd4c27021916bd09f7af32433a0eb542087cf0ae51cd4289c1c6d35ebfab79856ae';

        $script = ScriptFactory::create()->getScript();
        $this->assertEmpty($script->getBuffer()->getBinary());

        $script = ScriptFactory::create(Buffer::hex($hex))->getScript();
        $this->assertSame($script->getBuffer()->getHex(), $hex);
    }

    public function testPushHex()
    {
        $hex = '41';
        $expected = '01' . $hex;
        $data = Buffer::hex($hex);

        $script = ScriptFactory::create()
            ->push($data)
            ->getScript();

        $out = $script->getBuffer()->getHex();
        $this->assertSame($expected, $out);
    }

    public function testPushBuffer()
    {
        $hash = '0f9947c2b0fdd82ef3153232ee23d5c0bed84a02';
        $buf  = Buffer::hex($hash);
        $script = ScriptFactory::create()->push($buf)->getScript();

        $this->assertSame('14' . $hash, $script->getBuffer()->getHex());
    }

    public function testOp()
    {
        $op = 'OP_HASH160';
        $script = ScriptFactory::create()->op($op)->getScript();

        $rOp = $script->getOpcodes()->getOpByName($op);
        $expected = chr($rOp);
        $this->assertSame($script->getBuffer()->getBinary(), $expected);
    }

    /**
     * @depends testOp
     * @expectedException \RuntimeException
     */
    public function testOpFailure()
    {
        ScriptFactory::create()->op('OP_HASH666');
    }

    public function testPushdata1()
    {
        $data = Buffer::hex(
            '41414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141' .
            '4141414141414141414141414141414141414141414141414141414141414141'
        );
        $script = ScriptFactory::create()
            ->push($data)
            ->getScript();

        $result = $script->getScriptParser()->decode();
        $this->assertEquals(Opcodes::OP_PUSHDATA1, $result[0]->getOp());
        $this->assertEquals($data->getBinary(), $result[0]->getData()->getBinary());
    }

    public function testPushdata2()
    {
        $data = Buffer::hex(
            '41414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141' .
            '41414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141' .
            '41414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141' .
            '41414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141' .
            '41414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141' .
            '41414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141'
        );
        $script = ScriptFactory::create()
            ->push($data)
            ->getScript();

        $result = $script->getScriptParser()->decode();
        $this->assertEquals(Opcodes::OP_PUSHDATA2, $result[0]->getOp());
        $this->assertEquals($data->getBinary(), $result[0]->getData()->getBinary());
    }

    public function testGetScriptHash()
    {
        $script = $script = ScriptFactory::create()
            ->opcode(Opcodes::OP_2)
            ->data(
                Buffer::hex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb'),
                Buffer::hex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb'),
                Buffer::hex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb')
            )
            ->opcode(Opcodes::OP_3, Opcodes::OP_CHECKMULTISIG)
            ->getScript();

        $rs = new Script(Buffer::hex('522102cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb2102cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb2102cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb53ae'));

        // Ensure scripthash is being reproduced
        $this->assertSame($script->getHex(), '522102cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb2102cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb2102cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb53ae');
        $this->assertSame($script->getScriptHash()->getHex(), $rs->getScriptHash()->getHex());

        // Validate it's correct
        $this->assertSame($script->getScriptHash()->getHex(), 'f7c29c0c6d319e33c9250fca0cb61a500621d93e');
    }

    public function testGetVarInt()
    {
        $json = json_decode($this->dataFile('script.varint.json'));
        foreach ($json->test as $test) {
            $script = new Script(Buffer::hex($test->script));

            $this->assertSame(Buffertools::numToVarInt($script->getBuffer()->getSize())->getBinary(), pack("H*", $test->varint));
            $this->assertSame(Buffertools::numToVarInt($script->getBuffer()->getSize())->getHex(), $test->varint);
        }
    }

    public function getPushOnlyVectors()
    {
        return [
            [ScriptFactory::create()->data(new Buffer(), new Buffer())->opcode(Opcodes::OP_0)->getScript(), true],
            [ScriptFactory::create()->opcode(Opcodes::OP_1)->getScript(), true],
            [ScriptFactory::create()->opcode(Opcodes::OP_0)->getScript(), true],
            [ScriptFactory::create()->opcode(Opcodes::OP_16, Opcodes::OP_RESERVED)->getScript(), true],
            [ScriptFactory::create()->opcode(Opcodes::OP_16, Opcodes::OP_RESERVED)->getScript(), true],
            [ScriptFactory::create()->opcode(Opcodes::OP_NOP)->getScript(), false],

        ];
    }

    /**
     * @dataProvider getPushOnlyVectors
     * @param ScriptInterface $script
     * @param $eResult
     */
    public function testIsPushOnly(ScriptInterface $script, $eResult)
    {
        $this->assertEquals($eResult, $script->isPushOnly());
    }

    public function testEquals()
    {
        $script = new Script();
        $scriptEq = new Script();
        $scriptNotEq = new Script(new Buffer('AAAA'));

        $this->assertTrue($script->equals($scriptEq));
        $this->assertFalse($script->equals($scriptNotEq));
    }

    public function getWitnessVectors()
    {
        $array = json_decode($this->dataFile('witness.json'), true);
        $vectors = [];
        foreach ($array['test'] as $vector) {
            $script = new Script(Buffer::hex($vector['script']));
            $valid = $vector['valid'];
            $vectors[] = [$script, $valid];
        }

        return $vectors;
    }

    /**
     * @dataProvider getWitnessVectors
     * @param ScriptInterface $script
     * @param bool $valid
     */
    public function testWitnessVectors(ScriptInterface $script, $valid)
    {
        $witness = null;
        $isWitness = $script->isWitness($witness);
        if ($isWitness) {
            $this->assertInstanceOf(WitnessProgram::class, $witness);
        }

        $this->assertEquals($valid, $isWitness);
    }

    public function testDebugInfo()
    {
        $this->assertInternalType('array', (new Script)->__debugInfo());
    }

    public function testBadScriptZeroSigOps()
    {
        $script = new Script(new Buffer("\x41"));
        $this->assertEquals(0, $script->countSigOps());
        $this->assertEquals(0, $script->countP2shSigops(new Script));

        $p2sh = ScriptFactory::scriptPubKey()->payToScriptHash($script->getScriptHash());
        $this->assertEquals(0, $p2sh->countP2shSigOps(ScriptFactory::sequence([$script->getBuffer()])));

        $p2wsh = ScriptFactory::scriptPubKey()->witnessScriptHash(Hash::sha256($script->getBuffer()));
        $this->assertEquals(0, $p2wsh->countWitnessSigOps(new Script(), new ScriptWitness($script->getBuffer()), 0 | Interpreter::VERIFY_WITNESS));
    }
}
