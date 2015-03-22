<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Script\ScriptFactory;

class ScriptParserTest extends \PHPUnit_Framework_TestCase {


    public function getInvalidScripts()
    {
        $start = array(
            ['',255, null, false],
            ['0200',2,null, false],
            ['4c',76,null, false]
        );

        $s = '';
        for ($j = 1; $j < 250; $j++)
            $s .= '41';
        $start[] = ['4cff'.$s, 76, null, false];

        return $start;
    }

    public function getValidPushScripts()
    {
        $s = '';
        for ($j = 1; $j < 256; $j++)
            $s .= '41';
        $s1 = '4cff'.$s;

        $t = '';
        for ($j = 1; $j < 260; $j++)
            $t .= '41';
        $t1 = pack("cvH*", 0x4d, 260, $t);

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
     */
    public function testPush($script, $expectedOp, $expectedPushData, $result)
    {
        $parser = ScriptFactory::fromHex($script)->getScriptParser();
        $opCode = null;
        $pushdata = null;
        $this->assertEquals($result, $parser->next($opCode, $pushdata));
        $this->assertSame($expectedOp, $opCode);
        $this->assertSame($expectedPushData, $pushdata);
    }

    public function testDefaultParse()
    {
        $parse = ScriptFactory::create()->getScriptParser()->parse();
        $this->assertInternalType('array', $parse);
        $this->assertEmpty($parse);
    }

    public function testParse()
    {
        $buf = Buffer::hex('0f9947c2b0fdd82ef3153232ee23d5c0bed84a02');
        $this->script = ScriptFactory::create()->op('OP_HASH160')->push($buf)->op('OP_EQUAL');
        $parse = $this->script->getScriptParser()->parse();

        $this->assertSame($parse[0], 'OP_HASH160');
        $this->assertInstanceOf('BitWasp\Bitcoin\Buffer', $parse[1]);
        $this->assertSame($parse[1]->serialize(), $buf->serialize());
        $this->assertSame($parse[2], 'OP_EQUAL');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Opcode '250' not found
     */
    public function testParseInvalidOp()
    {
        $this->script = ScriptFactory::create(Buffer::hex('fa'));
        $this->script->getScriptParser()->parse();
    }

    public function testParseNullByte()
    {
        $null = chr(0x00);
        $this->script = ScriptFactory::create();
        $this->script->op('OP_0');
        $parse = $this->script->getScriptParser()->parse();
        $this->assertSame($parse[0]->serialize(), $null);
    }

    public function testParseScripts()
    {
        $f = file_get_contents(__DIR__ . '/../Data/script.asm.json');
        $json = json_decode($f);

        // Pay to pubkey hash
        $s0 = ScriptFactory::create(Buffer::hex($json->test[0]->script));

        $script0 = $s0->getScriptParser()->parse();
        $this->assertSame($script0[0], 'OP_DUP');
        $this->assertSame($script0[1], 'OP_HASH160');
        $this->assertSame($script0[2]->getSize(), 20);
        $this->assertSame($script0[3], 'OP_EQUALVERIFY');
        $this->assertSame($script0[4], 'OP_CHECKSIG');
        $this->assertSame($s0->getScriptParser()->getHumanReadable(), $json->test[0]->asm);

        // <65 bytes> OP_CHECKSIG - uncompressed paytopubkey
        $s1 = ScriptFactory::create(Buffer::hex($json->test[1]->script));

        $script1 = $s1->getScriptParser()->parse();
        $this->assertSame($script1[0]->getSize(), 65);
        $this->assertSame($script1[1], 'OP_CHECKSIG');
        $this->assertSame($s1->getScriptParser()->getHumanReadable(), $json->test[1]->asm);

        // pay to script hash output
        $s2 = ScriptFactory::create(Buffer::hex($json->test[2]->script));

        $script2 = $s2->getScriptParser()->parse();
        $this->assertSame($script2[0], 'OP_HASH160');
        $this->assertSame($script2[1]->getSize(), 20);
        $this->assertSame($script2[2], 'OP_EQUAL');

        // <33 bytes> OP_CHECKSIG - compressed paytopubkey
        $s3 = ScriptFactory::create(Buffer::hex($json->test[3]->script));
        $script3 = $s3->getScriptParser()->parse();
        $this->assertSame($script3[0]->getSize(), 33);
        $this->assertSame($script3[1], 'OP_CHECKSIG');

        // 1 <pubkey> <pubkey> OP_CHECKMULTISIG
        $s4 = ScriptFactory::create(Buffer::hex($json->test[4]->script));

        $script4 = $s4->getScriptParser()->parse();
        $this->assertSame($script4[0], 'OP_1');
        $this->assertSame($script4[1]->getSize(), 33);
        $this->assertSame($script4[2]->getSize(), 33);
        $this->assertSame($script4[3], 'OP_2');
        $this->assertSame($script4[4], 'OP_CHECKMULTISIG');

        // OP_RETURN <40 bytes>
        $s5 = ScriptFactory::create(Buffer::hex($json->test[5]->script));

        $script5 = $s5->getScriptParser()->parse();
        $this->assertSame($script5[0], 'OP_RETURN');
        $this->assertSame($script5[1]->getSize(), 38);

        // MtGox fuckup.
        $s6 = ScriptFactory::create(Buffer::hex($json->test[6]->script));
        $script6 = $s6->getScriptParser()->parse();
        $this->assertSame($script6[0], 'OP_DUP');
        $this->assertSame($script6[1], 'OP_HASH160');
        $this->assertSame($script6[2]->getSize(), 1);
        $this->assertSame($script6[3], 'OP_EQUALVERIFY');
        $this->assertSame($script6[4], 'OP_CHECKSIG');

        // OP_RETURN <38 bytes>
        $s7 = ScriptFactory::create(Buffer::hex($json->test[7]->script));
        $script7 = $s7->getScriptParser()->parse();
        $this->assertSame($script7[0], 'OP_RETURN');
        $this->assertSame($script7[1]->getSize(), 40);

        //
        $s8 = ScriptFactory::create(Buffer::hex($json->test[8]->script));
        $script8 = $s8->getScriptParser()->parse();
        $this->assertSame($script8[0], 'OP_IFDUP');
        $this->assertSame($script8[1], 'OP_IF');
        $this->assertSame($script8[2], 'OP_2SWAP');
        $this->assertSame($script8[3], 'OP_VERIFY');
        $this->assertSame($script8[4], 'OP_2OVER');
        $this->assertSame($script8[5], 'OP_DEPTH');
    }
}