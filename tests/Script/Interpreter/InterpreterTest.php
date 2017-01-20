<?php

namespace BitWasp\Bitcoin\Tests\Script\Interpreter;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\Interpreter\Checker;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Bitcoin\Script\Interpreter\Stack;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Buffertools\Buffer;

class InterpreterTest extends AbstractTestCase
{

    public function getScripts()
    {
        $flags = Interpreter::VERIFY_NONE;
        $vectors[] = [
            $flags,
            new Script(new Buffer()),
            ScriptFactory::create()->push(Buffer::hex($this->dataFile("10010bytes.hex")))->getScript(),
            false,
            new Transaction
        ];

        return $vectors;
    }


    /**
     * @param int $flags
     * @param ScriptInterface $scriptSig
     * @param ScriptInterface $scriptPubKey
     * @param $result
     * @param $tx
     * @dataProvider getScripts
     */
    public function testScript($flags, ScriptInterface $scriptSig, ScriptInterface $scriptPubKey, $result, $tx)
    {
        $ec = Bitcoin::getEcAdapter();
        $i = new Interpreter($ec, $tx);

        $stack = new Stack();
        $checker = new Checker($ec, new Transaction(), 0, 0);
        $i->evaluate($scriptSig, $stack, 0, $flags, $checker);
        $testResult = $i->evaluate($scriptPubKey, $stack, 0, $flags, $checker);

        $this->assertEquals($result, $testResult, ScriptFactory::fromHex($scriptSig->getHex() . $scriptPubKey->getHex())->getScriptParser()->getHumanReadable());
    }/**/

    public function testVerifyOnScriptSigFail()
    {
        $ec = Bitcoin::getEcAdapter();
        $f = 0;
        $i = new Interpreter($ec, new Transaction);
        $script = ScriptFactory::create()->op('OP_RETURN')->getScript();

        $this->assertFalse($i->verify($script, new Script, $f, new Checker($ec, new Transaction(), 0, 0)));
    }

    public function testVerifyOnScriptPubKeyFail()
    {
        $f = 0;
        $ec = Bitcoin::getEcAdapter();

        $i = new Interpreter($ec, new Transaction);
        $true = new Script(Buffer::hex('0101'));
        $false = ScriptFactory::create()->op('OP_RETURN')->getScript();
        $this->assertFalse($i->verify($true, $false, $f, new Checker($ec, new Transaction(), 0, 0)));
    }

    public function testVerifyEmptyAfterExec()
    {
        $f = 0;
        $ec = Bitcoin::getEcAdapter();

        $i = new Interpreter(Bitcoin::getEcAdapter(), new Transaction);
        $empty = new Script();
        $this->assertFalse($i->verify($empty, $empty, $f, new Checker($ec, new Transaction(), 0, 0)));
    }

    public function testVerifyNotFalse()
    {
        $true = new Script(Buffer::hex('0101'));
        $false = new Script(Buffer::hex('0100'));

        $ec = Bitcoin::getEcAdapter();

        $f = 0;
        $i = new Interpreter(Bitcoin::getEcAdapter(), new Transaction);
        $this->assertFalse($i->verify($true, $false, $f, new Checker($ec, new Transaction(), 0, 0)));
    }

    public function testP2shwithEmptyStack()
    {
        $ec = Bitcoin::getEcAdapter();

        $p2sh = new Script();
        $output = ScriptFactory::scriptPubKey()->payToScriptHash(Hash::sha256ripe160($p2sh->getBuffer()));
        $scriptSig = new Script();

        $f = InterpreterInterface::VERIFY_P2SH;
        $i = new Interpreter(Bitcoin::getEcAdapter(), new Transaction);
        $this->assertFalse($i->verify($scriptSig, $output, $f, new Checker($ec, new Transaction(), 0, 0)));
    }

    public function testInvalidPayToScriptHash()
    {
        $ec = Bitcoin::getEcAdapter();
        $p2sh = ScriptFactory::create()->op('OP_RETURN')->getScript();
        $scriptPubKey = ScriptFactory::scriptPubKey()->payToScriptHash(Hash::sha256ripe160($p2sh->getBuffer()));
        $scriptSig = ScriptFactory::create()->op('OP_0')->push($p2sh->getBuffer())->getScript();

        $f = InterpreterInterface::VERIFY_P2SH;
        $i = new Interpreter($ec, new Transaction);
        $this->assertFalse($i->verify($scriptSig, $scriptPubKey, $f, new Checker($ec, new Transaction(), 0, 0)));
    }

    public function testVerifyScriptsigMustBePushOnly()
    {
        $ec = Bitcoin::getEcAdapter();
        $p2sh = ScriptFactory::create()->op('OP_1')->push(Buffer::hex('41414141'))->op('OP_DEPTH')->getScript();
        $scriptSig = ScriptFactory::create()->op('OP_DEPTH')->push($p2sh->getBuffer())->getScript();
        $scriptPubKey = ScriptFactory::scriptPubKey()->payToScriptHash(Hash::sha256ripe160($p2sh->getBuffer()));

        $f = InterpreterInterface::VERIFY_P2SH;
        $i = new Interpreter($ec, new Transaction);
        $this->assertFalse($i->verify($scriptSig, $scriptPubKey, $f, new Checker($ec, new Transaction(), 0, 0)));
    }

    public function testCheckMinimalPush()
    {
        $valid = [
            [0, Buffer::hex('')],
            [81, Buffer::hex('01')],
            [79, Buffer::hex('81')],
            [5, Buffer::hex('0102030405')],
            [0x4c, new Buffer('', 76)],
            [0x4c, new Buffer('', 78)],
            [0x4c, new Buffer('', 255)],
            [0x4d, new Buffer('', 256)],
            [0x4d, new Buffer('', 65535)],
            [0x4e, new Buffer('', 65536)],
        ];

        $invalid = [
            [0x81, new Buffer('')],
            [01, Buffer::hex('0102030405')],
            [0x4d, new Buffer('', 74)],
            [0x4e, new Buffer('', 255)],
            [0x4d, new Buffer('', 255)]
        ];

        $i = new Interpreter(Bitcoin::getEcAdapter());
        foreach ($valid as $t) {
            list ($opcode, $buffer) = $t;
            $this->assertTrue($i->checkMinimalPush($opcode, $buffer));
        }

        foreach ($invalid as $t) {
            list ($opcode, $buffer) = $t;
            $this->assertFalse($i->checkMinimalPush($opcode, $buffer));
        }
    }
}
