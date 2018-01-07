<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script\Parser;

use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class ParserTest extends AbstractTestCase
{

    /**
     * @var ScriptInterface
     */
    private $script;

    public function getInvalidScripts()
    {
        $start = array(
            ['',255, null, false],
            ['0200',2,null, false],
            ['4c',76,null, false]
        );

        $s = '';
        for ($j = 1; $j < 250; $j++) {
            $s .= '41';
        }
        $start[] = ['4cff'.$s, 76, null, false];

        return $start;
    }

    public function getValidPushScripts()
    {
        $s = '';
        for ($j = 1; $j < 256; $j++) {
            $s .= '41';
        }
        $s1 = '4cff'.$s;

        $t = '';
        for ($j = 1; $j < 260; $j++) {
            $t .= '41';
        }
        //$t1 = pack("cvH*", 0x4d, 260, $t);

        $start = [
            ['0100', 1, chr(0), true],
            [$s1, 76, pack("H*", $s), true],
            //[bin2hex($t1), 77, pack("H*", $t), true]
        ];
        return $start;
    }

    public function getTestPushScripts()
    {
        return array_merge($this->getValidPushScripts(), $this->getInvalidScripts());
    }

    /**
     * @dataProvider getTestPushScripts
     * @param $script
     * @param $expectedOp
     * @param $expectedPushData
     * @param $result
     */
    public function testPush($script, $expectedOp, $expectedPushData, $result)
    {
        $parser = ScriptFactory::fromHex($script)->getScriptParser();

        $result = $parser->next();

        if ($result !== null) {
            if ($result->isPush()) {
                $data = $result->getData();
                if ($data->getSize() > 0) {
                    $this->assertSame($expectedPushData, $data->getBinary());
                }
            }
        }
    }

    public function testParse()
    {
        $buf = Buffer::hex('0f9947c2b0fdd82ef3153232ee23d5c0bed84a02');
        $script = ScriptFactory::create()->opcode(Opcodes::OP_HASH160)->push($buf)->opcode(Opcodes::OP_EQUAL)->getScript();
        $parse = $script->getScriptParser()->decode();
        $this->assertFalse($parse[0]->isPush());
        $this->assertSame($parse[0]->getOp(), Opcodes::OP_HASH160);

        $this->assertTrue($parse[1]->isPush());
        $this->assertSame($parse[1]->getData()->getBinary(), $buf->getBinary());

        $this->assertFalse($parse[2]->isPush());
        $this->assertSame($parse[2]->getOp(), Opcodes::OP_EQUAL);
    }

    public function testParseNullByte()
    {
        $script = ScriptFactory::create()->opcode(Opcodes::OP_0)->getScript();
        $parse = $script->getScriptParser()->decode();
        $data = $parse[0];
        $this->assertEquals(Opcodes::OP_0, $data->getOp());
        $this->assertSame(0, $data->getDataSize());
        $this->assertSame('', $data->getData()->getBinary());
    }

    public function testParseScripts()
    {
        $f = $this->dataFile('script.asm.json');
        $json = json_decode($f);

        // Pay to pubkey hash
        $s0 = ScriptFactory::create(Buffer::hex($json->test[0]->script))->getScript();

        $script0 = $s0->getScriptParser()->decode();
        $this->assertSame(Opcodes::OP_DUP, $script0[0]->getOp());
        $this->assertSame(Opcodes::OP_HASH160, $script0[1]->getOp());
        $this->assertTrue($script0[2]->isPush());
        $this->assertSame(20, $script0[2]->getDataSize());
        $this->assertSame(Opcodes::OP_EQUALVERIFY, $script0[3]->getOp());
        $this->assertSame(Opcodes::OP_CHECKSIG, $script0[4]->getOp());

        $this->assertSame($s0->getScriptParser()->getHumanReadable(), $json->test[0]->asm);

        // <65 bytes> OP_CHECKSIG - uncompressed paytopubkey
        $s1 = ScriptFactory::create(Buffer::hex($json->test[1]->script))->getScript();

        $script1 = $s1->getScriptParser()->decode();
        $this->assertTrue($script1[0]->isPush());
        $this->assertSame(65, $script1[0]->getDataSize());
        $this->assertSame(Opcodes::OP_CHECKSIG, $script1[1]->getOp());
        $this->assertSame($s1->getScriptParser()->getHumanReadable(), $json->test[1]->asm);

        // pay to script hash output
        $s2 = ScriptFactory::create(Buffer::hex($json->test[2]->script))->getScript();

        $script2 = $s2->getScriptParser()->decode();
        $this->assertSame(Opcodes::OP_HASH160, $script2[0]->getOp());
        $this->assertTrue($script2[1]->isPush());
        $this->assertSame(20, $script2[1]->getDataSize());
        $this->assertSame(Opcodes::OP_EQUAL, $script2[2]->getOp());

        // <33 bytes> OP_CHECKSIG - compressed paytopubkey
        $s3 = ScriptFactory::create(Buffer::hex($json->test[3]->script))->getScript();
        $script3 = $s3->getScriptParser()->decode();
        $this->assertTrue($script3[0]->isPush());
        $this->assertSame(33, $script3[0]->getDataSize());
        $this->assertSame($script3[1]->getOp(), Opcodes::OP_CHECKSIG);

        // 1 <pubkey> <pubkey> OP_CHECKMULTISIG
        $s4 = ScriptFactory::create(Buffer::hex($json->test[4]->script))->getScript();

        $script4 = $s4->getScriptParser()->decode();
        $this->assertSame(Opcodes::OP_1, $script4[0]->getOp());
        $this->assertSame(33, $script4[1]->getDataSize());
        $this->assertSame(33, $script4[2]->getDataSize());
        $this->assertSame(Opcodes::OP_2, $script4[3]->getOp());
        $this->assertSame(Opcodes::OP_CHECKMULTISIG, $script4[4]->getOp());

        // OP_RETURN <40 bytes>
        $s5 = ScriptFactory::create(Buffer::hex($json->test[5]->script))->getScript();

        $script5 = $s5->getScriptParser()->decode();
        $this->assertSame(Opcodes::OP_RETURN, $script5[0]->getOp());
        $this->assertSame(38, $script5[1]->getDataSize());

        // MtGox fuckup.
        $s6 = ScriptFactory::create(Buffer::hex($json->test[6]->script))->getScript();
        $script6 = $s6->getScriptParser()->decode();
        $this->assertSame(Opcodes::OP_DUP, $script6[0]->getOp());
        $this->assertSame(Opcodes::OP_HASH160, $script6[1]->getOp());
        $this->assertSame(0, $script6[2]->getDataSize());
        $this->assertSame(Opcodes::OP_EQUALVERIFY, $script6[3]->getOp());
        $this->assertSame(Opcodes::OP_CHECKSIG, $script6[4]->getOp());

        // OP_RETURN <38 bytes>
        $s7 = ScriptFactory::create(Buffer::hex($json->test[7]->script))->getScript();
        $script7 = $s7->getScriptParser()->decode();
        $this->assertSame(Opcodes::OP_RETURN, $script7[0]->getOp());
        $this->assertSame(40, $script7[1]->getDataSize());

        //
        $s8 = ScriptFactory::create(Buffer::hex($json->test[8]->script))->getScript();
        $script8 = $s8->getScriptParser()->decode();
        $this->assertSame(Opcodes::OP_IFDUP, $script8[0]->getOp());
        $this->assertSame(Opcodes::OP_IF, $script8[1]->getOp());
        $this->assertSame(Opcodes::OP_2SWAP, $script8[2]->getOp());
        $this->assertSame(Opcodes::OP_VERIFY, $script8[3]->getOp());
        $this->assertSame(Opcodes::OP_2OVER, $script8[4]->getOp());
        $this->assertSame(Opcodes::OP_DEPTH, $script8[5]->getOp());
    }

    public function testDataSize()
    {
        $buffer = new Buffer('', 40);
        $script = ScriptFactory::create()->push($buffer)->opcode(Opcodes::OP_HASH160)->getScript();
        $parsed = $script->getScriptParser();

        for ($i = 0; $i < 1; $i++) {
            $operation = $parsed->current();
            $this->assertEquals($operation->getData()->getSize(), $operation->getDataSize());
        }
    }
}
