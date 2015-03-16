<?php

namespace Afk11\Bitcoin\Tests\Script;

use Afk11\Bitcoin\Key\PublicKeyFactory;
use Afk11\Bitcoin\Script\Script;
use Afk11\Bitcoin\Script\ScriptFactory;
use Afk11\Bitcoin\Key\PublicKey;
use Afk11\Bitcoin\Buffer;

class ScriptTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Script
     */
    protected $script;

    /**
     * @var string
     */
    protected $bufferType;

    public function __construct()
    {
        $this->bufferType = 'Afk11\Bitcoin\Buffer';
    }

    public function setUp()
    {
        $this->script = new Script();
    }

    public function testGetOpCodes()
    {
        $opCodes = $this->script->getOpCodes();
        $this->assertInstanceOf('Afk11\Bitcoin\Script\Opcodes', $opCodes);
    }

    public function testDefaultSerializeBinary()
    {
        $val = $this->script->getBuffer()->serialize();
        $this->assertEmpty($val);
    }

    public function testDefaultSerializeHex()
    {
        $val = $this->script->getBuffer()->serialize('hex');
        $this->assertEmpty($val);
    }

    public function testSerializeBinary()
    {
        $val = $this->script->getBuffer()->serialize();
        $this->assertEmpty($val);
    }

    public function testSerializeHex()
    {
        $val = $this->script->getBuffer()->serialize('hex');
        $this->assertEmpty($val);
    }

    public function testGetScript()
    {
        $hex = '00483045022057e65d83fb50768f310953300cdf09e8c551a716de81eb9e9bea2b055cffce53022100830c1636104d5ba704ef92849db0415182c364278b7f2a53097b65beb1c755c001483045022100b16c0cf3d6e16a9f9a2559c0043c083e46a8557df1f22755e396b94b08e8624202203b6a9927ceb70eda3e71f584dffe108ef0fcc5040538de45f85c1645b115168601473044022006135422817bd9f8cd24004c9797114838944a7594b6d9d7da043c93700c58bf0220009c226d944fc1d2c4a29d9b687aab04f2f65f9688c468594a0747067fa717800149304602210093f6c1402fdefd71e890168f8a2eb34ff18b50a9babdfd1b4a69c8895b10a9bb022100b7fea02dbc6391ac6403f628afe576c2e8b42f7d31c7c38d959766b45e114f6e01483045022100f6d4fa96d2d221cc0368b0da1fafc889c5212e1a65a5d7b5937d374993568bb002206fc78de031d1cd34b203abedac0ef628ad6c863a0c505533da12cf34bf74fdba01483045022100b52f4d6f1e69554f15b9e02be1a3f03d96943c2aa21544047d9156b91a2eace5022023b41bef3725b1a6cab9c509b95e3a2f839536325597a2359ea0c14786adf2a8014ccf5621025d951ab5a9c3656aa25b4facf7b9824ca3cca7f9eaf3b84551d3aef8b0803a5721027b7eb1910184738f54b00ee7c5f695598d0f21b8ea87bface1e9d901fa5193802102e8537cc8081358b9bbcbd221da7f10ec167fbadcb03b8ff2980c8a78aca076712102f2d0f1996cf932b766032ea1da0051d8e7688516eb005b9ffd6acfbf032627c321030bd27f6a978bc03748b301e20531dd76f27ddcc25e51c09e65a6e4dafa8abbaf21037bd4c27021916bd09f7af32433a0eb542087cf0ae51cd4289c1c6d35ebfab79856ae';

        $script = ScriptFactory::create();
        $this->assertEmpty($script->getBuffer()->serialize());

        $script = ScriptFactory::create(Buffer::hex($hex));
        $this->assertSame($script->getBuffer()->serialize('hex'), $hex);
    }

    public function testPushHex()
    {
        $hex = '41';
        $expected = '01' . $hex;
        $data = Buffer::hex($hex);

        $this->script->push($data);
        $out = $this->script->getBuffer()->serialize('hex');
        $this->assertSame($expected, $out);
    }

    public function testPushBuffer()
    {
        $hash = '0f9947c2b0fdd82ef3153232ee23d5c0bed84a02';
        $buf  = Buffer::hex($hash);
        $this->script->push($buf);

        $out = $this->script->getBuffer()->serialize('hex');
        $this->assertSame('14' . $hash, $out);
    }

    public function testOp()
    {
        $op = 'OP_HASH160';
        $this->script->op($op);

        $rOp = $this->script->getOpCodes()->getOpByName($op);
        $expected = chr($rOp);
        $this->assertSame($this->script->getBuffer()->serialize(), $expected);
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









    public function testPushdata1()
    {
        $data = Buffer::hex(
            '41414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141' .
            '4141414141414141414141414141414141414141414141414141414141414141'
        );
        $this->script->push($data);
        $script = $this->script->getBuffer()->serialize();
        $firstOpCode = ord($script[0]);
        $this->assertSame($firstOpCode, $this->script->getOpCodes()->getOpByName('OP_PUSHDATA1'));
        $this->script->getScriptParser()->parse();
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
        $script = $this->script->getBuffer()->serialize();
        $firstOpCode = ord($script[0]);
        $this->assertSame($firstOpCode, $this->script->getOpCodes()->getOpByName('OP_PUSHDATA2'));
        $this->script->getScriptParser()->parse();
    }

    public function testPayToPubKey()
    {
        $pubkey = PublicKeyFactory::fromHex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb');
        $script = ScriptFactory::scriptPubKey()->payToPubKey($pubkey);
        $parsed = $script->getScriptParser()->parse();
        $this->assertSame($parsed[0]->serialize('hex'), '02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb');
        $this->assertSame($parsed[1], 'OP_CHECKSIG');
    }

    public function testPayToPubKeyHash()
    {
        $pubkey = PublicKeyFactory::fromHex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb');
        $script = ScriptFactory::scriptPubKey()->payToPubKeyHash($pubkey);
        $parsed = $script->getScriptParser()->parse();
        $this->assertSame($parsed[0], 'OP_DUP');
        $this->assertSame($parsed[1], 'OP_HASH160');
        $this->assertSame($parsed[2]->serialize('hex'), 'f0cd7fab8e8f4b335931a77f114a46039068da59');
        $this->assertSame($parsed[3], 'OP_EQUALVERIFY');
    }

    public function testGetScriptHash()
    {
        $script = new Script();
        $script
            ->op('OP_2')
            ->push('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb')
            ->push('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb')
            ->push('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb')
            ->op('OP_3')
            ->op('OP_CHECKMULTISIG');

        $rs = new Script(Buffer::hex('522102cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb2102cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb2102cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb53ae'));

        // Ensure scripthash is being reproduced
        $this->assertSame($script->getBuffer()->serialize('hex'), '522102cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb2102cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb2102cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb53ae');
        $this->assertSame($script->getScriptHash()->serialize('hex'), $rs->getScriptHash()->serialize('hex'));

        // Validate it's correct
        $this->assertSame($script->getScriptHash()->serialize('hex'), 'f7c29c0c6d319e33c9250fca0cb61a500621d93e');

    }

    public function testPayToScriptHash()
    {
        // Script::payToScriptHash should produce a ScriptHash type script, from a different script
        $script = new Script();
        $script
            ->op('OP_2')
            ->push('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb')
            ->push('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb')
            ->push('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb')
            ->op('OP_3')
            ->op('OP_CHECKMULTISIG');

        $scriptHash = ScriptFactory::scriptPubKey()->payToScriptHash($script);
        $parsed     = $scriptHash->getScriptParser()->parse();
        $this->assertSame($parsed[0], 'OP_HASH160');
        $this->assertSame($parsed[1]->serialize('hex'), 'f7c29c0c6d319e33c9250fca0cb61a500621d93e');
        $this->assertSame($parsed[2], 'OP_EQUAL');
    }

    public function testGetVarInt()
    {
        $f = file_get_contents(__DIR__ . '/../Data/script.varint.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $script = new Script(Buffer::hex($test->script));

            $this->assertSame($script->getBuffer()->getVarInt()->serialize(), pack("H*", $test->varint));
            $this->assertSame($script->getBuffer()->getVarInt()->serialize('hex'), $test->varint);
        }
    }
}
