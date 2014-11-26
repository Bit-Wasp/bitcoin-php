<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 22/11/14
 * Time: 19:35
 */

namespace Bitcoin;

use Bitcoin\Util\Buffer;

class ScriptTest extends \PHPUnit_Framework_TestCase
{

    protected $script;

    public function setUp()
    {
        $this->script = new Script();
    }

    public function testGetOpCodes()
    {
        $opCodes = $this->script->getOpCodes();
        $this->assertInternalType('array', $opCodes);
    }

    public function testGetRegisteredOpCodes()
    {
        $reversed = array_flip($this->script->getOpCodes());

        $rOpCodes = $this->script->getRegisteredOpCodes();
        $this->assertSame($reversed, $rOpCodes);

    }

    public function testGetRegisteredOpCode()
    {
        // Check getRegisteredOpCode returns the right operation
        $expected = 'OP_0';
        $val = $this->script->getRegisteredOpCode(0);

        $this->assertSame($expected, $val);
    }

    /**
     * @depends testGetRegisteredOpCode
     * @expectedException \Exception
     */
    public function testGetRegisteredOpCodeException()
    {
        $val = $this->script->getRegisteredOpCode(3);
    }

    public function testGetOpCode()
    {
        $expected = 0;
        $val = $this->script->getOpCode('OP_0');
        $this->assertSame($expected, $val);
    }

    /**
     * @depends testGetOpCode
     * @expectedException \Exception
     */
    public function testGetOpCodeException()
    {
        $val = $this->script->getOpCode(3);
    }

    public function testDefaultSerializeBinary()
    {
        $val = $this->script->serialize();
        $this->assertEmpty($val);
    }

    public function testDefaultSerializeHex()
    {
        $val = $this->script->serialize('hex');
        $this->assertEmpty($val);
    }

    public function testSerializeBinary()
    {
        $val = $this->script->serialize();
        $this->assertEmpty($val);
    }

    public function testSerializeHex()
    {
        $val = $this->script->serialize('hex');
        $this->assertEmpty($val);
    }

    public function testDefaultGetSizeBinary()
    {
        $size = $this->script->getSize();
        $this->assertSame(0, $size);
    }

    public function testDefaultGetSizeHex()
    {
        $size = $this->script->getSize('hex');
        $this->assertSame(0, $size);
    }

    public function testGetSizeBinary()
    {
        $this->script->op('OP_HASH160');
        $size = $this->script->getSize();
        $this->assertSame(1, $size);
    }

    public function testGetSizeBinaryAfterPush()
    {
        $this->script->op('OP_HASH160');
        $size = $this->script->getSize();
        $this->assertSame(1, $size);

        $push = '41414141';
        $this->script->push($push);
        $this->assertSame(6, $this->script->getSize());
        // Why 6? the 4 bytes that were pushed are serialized with 1 byte for the length!
    }

    public function testGetSizeHex()
    {
        $this->script->op('OP_HASH160');
        $size = $this->script->getSize('hex');
        $this->assertSame(2, $size);
    }

    public function testGetSizeHexAfterPush()
    {
        $this->script->op('OP_HASH160');
        $size = $this->script->getSize('hex');
        $this->assertSame(2, $size);

        $push = '41414141';
        $this->script->push($push);
        $this->assertSame(12, $this->script->getSize('hex'));
        // Why 6? the 4 bytes that were pushed are serialized with 1 byte for the length!
    }

    public function testSetScript()
    {
        $hex = '00483045022057e65d83fb50768f310953300cdf09e8c551a716de81eb9e9bea2b055cffce53022100830c1636104d5ba704ef92849db0415182c364278b7f2a53097b65beb1c755c001483045022100b16c0cf3d6e16a9f9a2559c0043c083e46a8557df1f22755e396b94b08e8624202203b6a9927ceb70eda3e71f584dffe108ef0fcc5040538de45f85c1645b115168601473044022006135422817bd9f8cd24004c9797114838944a7594b6d9d7da043c93700c58bf0220009c226d944fc1d2c4a29d9b687aab04f2f65f9688c468594a0747067fa717800149304602210093f6c1402fdefd71e890168f8a2eb34ff18b50a9babdfd1b4a69c8895b10a9bb022100b7fea02dbc6391ac6403f628afe576c2e8b42f7d31c7c38d959766b45e114f6e01483045022100f6d4fa96d2d221cc0368b0da1fafc889c5212e1a65a5d7b5937d374993568bb002206fc78de031d1cd34b203abedac0ef628ad6c863a0c505533da12cf34bf74fdba01483045022100b52f4d6f1e69554f15b9e02be1a3f03d96943c2aa21544047d9156b91a2eace5022023b41bef3725b1a6cab9c509b95e3a2f839536325597a2359ea0c14786adf2a8014ccf5621025d951ab5a9c3656aa25b4facf7b9824ca3cca7f9eaf3b84551d3aef8b0803a5721027b7eb1910184738f54b00ee7c5f695598d0f21b8ea87bface1e9d901fa5193802102e8537cc8081358b9bbcbd221da7f10ec167fbadcb03b8ff2980c8a78aca076712102f2d0f1996cf932b766032ea1da0051d8e7688516eb005b9ffd6acfbf032627c321030bd27f6a978bc03748b301e20531dd76f27ddcc25e51c09e65a6e4dafa8abbaf21037bd4c27021916bd09f7af32433a0eb542087cf0ae51cd4289c1c6d35ebfab79856ae';

        $script = new Script();
        $this->assertEmpty($script->serialize());

        $script->set($hex);
        $this->assertSame($script->serialize('hex'), $hex);
    }


    public function testDefaultParse()
    {
        $parse = $this->script->parse();
        $this->assertInternalType('array', $parse);
        $this->assertEmpty($parse);
    }

    public function testNumToVarInt()
    {
        // Should not prefix with anything. Just return chr($decimal);
        for ($i = 0; $i < 253; $i++) {
            $decimal = $i;
            $expected = chr($decimal);
            $val = $this->script->numToVarInt($decimal);

            $this->assertSame($expected, $val);
       }
    }

    public function testNumToVarInt1LowerFailure()
    {
        // Decimal of this size does not take a prefix
        $decimal  = 0xfc; // 252;
        $prefixOp = 0xfd;
        $expected = pack("Cv", $prefixOp, $decimal);
        $val = $this->script->numToVarInt($decimal);
        $this->assertNotSame($expected, $val);
    }
    public function testNumToVarInt1Lowest()
    {
        // Decimal > 253 requires a prefix
        $prefixOp = 0xfd;
        $decimal  = 0xfd;
        $expected = pack("Cv", $prefixOp, $decimal);
        $val = $this->script->numToVarInt($decimal);
        $this->assertSame($expected, $val);
    }
    public function testNumToVarInt1Upper()
    {
        // This prefix is used up to 0xffff, because if we go higher,
        // the prefixes are no longer in agreement

        $prefixOp = 0xfd;
        $decimal  = 0xffff;
        $expected = pack("Cv", $prefixOp, $decimal);
        $val = $this->script->numToVarInt($decimal);
        $this->assertSame($expected, $val);
    }
    public function testNumToVarInt1UpperFailure()
    {
        // here the inconsistency occurs - look!
        $decimal  = 0xffff + 1;
        $prefixOp = 0xfd;
        $expected = pack("Cv", $prefixOp, $decimal);

        $val = $this->script->numToVarInt($decimal);
        $this->assertNotSame($expected, $val);
    }

    public function testNumToVarInt2LowerFailure()
    {
        // We can check that numbers this low don't yield a 0xfe prefix
        $prefixOp = 0xfe;
        $decimal  = 0xffff;
        $expected = pack("CV", $prefixOp, $decimal);
        $val = $this->script->numToVarInt($decimal);
        $this->assertNotSame($expected, $val);
    }

    public function testNumToVarInt2Lowest()
    {
        // With this prefix, check that the lowest for this field IS prefictable.
        $prefixOp = 0xfe;
        $decimal  = 0xffff + 1;
        $expected = pack("CV", $prefixOp, $decimal);
        $val = $this->script->numToVarInt($decimal);
        $this->assertSame($expected, $val);
    }

    public function testNumToVarInt2Upper()
    {
        // Last number that will share 0xfe prefix: 2^32
        $prefixOp = 0xfe;
        $decimal  = 0xffffffff;
        $expected = pack("CV", $prefixOp, $decimal);
        $val = $this->script->numToVarInt($decimal);
        $this->assertSame($expected, $val);
    }

    /**
     * @expectedException \Exception
     */
    public function testNumToVarIntOutOfRange()
    {
        // Check that this is out of range (PHP's fault)
        $prefixOp = 0xfe;
        $decimal  = 0xffffffff + 1;                             // 2^32 - 1
        $this->script->numToVarInt($decimal);
    }

    public function testPushHex()
    {
        $hex = '41';
        $expected = '01' . $hex;
        $data = Buffer::hex($hex);

        $this->script->push($data);
        $out = $this->script->serialize('hex');
        $this->assertSame($expected, $out);
    }

    public function testPushBuffer()
    {
        $hash = '0f9947c2b0fdd82ef3153232ee23d5c0bed84a02';
        $buf  = Buffer::hex($hash);
        $this->script->push($buf);

        $out = $this->script->serialize('hex');
        $this->assertSame('14' . $hash, $out);
    }

    public function testOp()
    {
        $op = 'OP_HASH160';
        $this->script->op($op);

        $rOp = $this->script->getOpCode($op);
        $expected = chr($rOp);
        $this->assertSame($this->script->serialize(), $expected);
    }

    /**
     * @depends testOp
     * @expectedException \RuntimeException
     */
    public function testOpFailure()
    {
        $op = 'OP_HASH666';
        $this->script->op($op);
    }

    public function testROp()
    {
        $rop = 173;
        $this->script->rOp($rop);
        $this->assertSame($this->script->serialize(), chr($rop));
    }

    /**
     * @depends testROp
     * @expectedException \RuntimeException
     */
    public function testROpFailure()
    {
        $rop = 999;
        $this->script->rOp($rop);
        $this->assertSame($this->script->serialize(), chr($rop));
    }

    public function testParse()
    {
        $buf = Buffer::hex('0f9947c2b0fdd82ef3153232ee23d5c0bed84a02');
        $this->script->op('OP_HASH160')->push($buf)->op('OP_EQUAL');
        $parse = $this->script->parse();

        $this->assertSame($parse[0], 'OP_HASH160');
        $this->assertInstanceOf('Bitcoin\Util\Buffer', $parse[1]);
        $this->assertSame($parse[1]->serialize(), $buf->serialize());
        $this->assertSame($parse[2], 'OP_EQUAL');
    }

    public function testParseNullByte()
    {
        $null = chr(0x00);
        $this->script->op('OP_0');
        $parse = $this->script->parse();
        $this->assertSame($parse[0]->serialize(), $null);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unknown opcode: 250
     */
    public function testParseInvalidOp()
    {
        $this->script->set(Buffer::hex('fa'));
        $this->script->parse();
    }

    public function testParseScripts()
    {
        $f = file_get_contents(__DIR__ . '/../Data/script.asm.json');
        $json = json_decode($f);

        // Pay to pubkey hash
        $s0 = new Script();
        $s0->set($json->test[0]->script);
        $script0 = $s0->parse();
        $this->assertSame($script0[0], 'OP_DUP');
        $this->assertSame($script0[1], 'OP_HASH160');
        $this->assertSame($script0[2]->getSize(), 20);
        $this->assertSame($script0[3], 'OP_EQUALVERIFY');
        $this->assertSame($script0[4], 'OP_CHECKSIG');
        $this->assertSame($s0->getAsm(), $json->test[0]->asm);

        // <65 bytes> OP_CHECKSIG - uncompressed paytopubkey
        $s1 = new Script();
        $s1->set($json->test[1]->script);
        $script1 = $s1->parse();
        $this->assertSame($script1[0]->getSize(), 65);
        $this->assertSame($script1[1], 'OP_CHECKSIG');
        $this->assertSame($s1->getAsm(), $json->test[1]->asm);

        // pay to script hash output
        $s2 = new Script();
        $s2->set($json->test[2]->script);
        $script2 = $s2->parse();
        $this->assertSame($script2[0], 'OP_HASH160');
        $this->assertSame($script2[1]->getSize(), 20);
        $this->assertSame($script2[2], 'OP_EQUAL');

        // <33 bytes> OP_CHECKSIG - compressed paytopubkey
        $s3 = new Script();
        $s3->set($json->test[3]->script);
        $script3 = $s3->parse();
        $this->assertSame($script3[0]->getSize(), 33);
        $this->assertSame($script3[1], 'OP_CHECKSIG');

        // 1 <pubkey> <pubkey> OP_CHECKMULTISIG
        $s4 = new Script();
        $s4->set($json->test[4]->script);
        $script4 = $s4->parse();
        $this->assertSame($script4[0], 'OP_1');
        $this->assertSame($script4[1]->getSize(), 33);
        $this->assertSame($script4[2]->getSize(), 33);
        $this->assertSame($script4[3], 'OP_2');
        $this->assertSame($script4[4], 'OP_CHECKMULTISIG');

        // OP_RETURN <40 bytes>
        $s5 = new Script();
        $s5->set($json->test[5]->script);
        $script5 = $s5->parse();
        $this->assertSame($script5[0], 'OP_RETURN');
        $this->assertSame($script5[1]->getSize(), 38);

        // MtGox fuckup.
        $s6 = new Script();
        $s6->set($json->test[6]->script);
        $script6 = $s6->parse();
        $this->assertSame($script6[0], 'OP_DUP');
        $this->assertSame($script6[1], 'OP_HASH160');
        $this->assertSame($script6[2]->getSize(), 1);
        $this->assertSame($script6[3], 'OP_EQUALVERIFY');
        $this->assertSame($script6[4], 'OP_CHECKSIG');

        // OP_RETURN <38 bytes>
        $s7 = new Script();
        $s7->set($json->test[7]->script);
        $script7 = $s7->parse();
        $this->assertSame($script7[0], 'OP_RETURN');
        $this->assertSame($script7[1]->getSize(), 40);

        //
        $s8 = new Script();
        $s8->set($json->test[8]->script);
        $script8 = $s8->parse();
        $this->assertSame($script8[0], 'OP_IFDUP');
        $this->assertSame($script8[1], 'OP_IF');
        $this->assertSame($script8[2], 'OP_2SWAP');
        $this->assertSame($script8[3], 'OP_VERIFY');
        $this->assertSame($script8[4], 'OP_2OVER');
        $this->assertSame($script8[5], 'OP_DEPTH');
    }

    public function testPushdata1()
    {
        $data = Buffer::hex(
            '41414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141' .
            '4141414141414141414141414141414141414141414141414141414141414141');
        $this->script->push($data);
        $script = $this->script->serialize();
        $firstOpCode = ord($script[0]);
        $this->assertSame($firstOpCode, $this->script->getOpCode('OP_PUSHDATA1'));
        $this->script->parse();
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

        $this->script->push($data);
        $script = $this->script->serialize();
        $firstOpCode = ord($script[0]);
        $this->assertSame($firstOpCode, $this->script->getOpCode('OP_PUSHDATA2'));
        $this->script->parse();
    }


}
