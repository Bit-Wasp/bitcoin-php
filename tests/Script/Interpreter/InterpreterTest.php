<?php

namespace BitWasp\Bitcoin\Tests\Script\Interpreter;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\ConsensusFactory;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Bitcoin\Script\RedeemScript;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\ScriptStack;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Transaction\TransactionBuilder;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;
use Mdanter\Ecc\EccFactory;

class InterpreterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $flagStr
     * @return Flags
     */
    private function setFlags($flagStr)
    {
        $array = explode(",", $flagStr);
        $int = 0;
        $checkdisabled = false;
        foreach ($array as $activeFlag) {
            $f = constant('\BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface::'.$activeFlag);
            $int |= $f;
        }

        return new Flags($int, $checkdisabled);
    }

    public function testGetStackState()
    {
        $i = new Interpreter(Bitcoin::getEcAdapter(), new Transaction(), new Flags(0));
        $testStack = new ScriptStack();
        $testStack->push('a');

        $this->assertEquals(0, $i->getStackState()->getMainStack()->size());
        $this->assertEquals(0, $i->getStackState()->getAltStack()->size());
        $this->assertEquals(0, $i->getStackState()->getAltStack()->size());
        $i->getStackState()->restoreMainStack($testStack);
        $this->assertEquals($testStack, $i->getStackState()->getMainStack());

    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\ScriptRuntimeException
     * @expectedExceptionMessage Signature with incorrect encoding
     */
    public function testIsLowDERFailsWithIncorrectEncoding()
    {
        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction(), new Flags(0));
        $i->isLowDerSignature(new Buffer('abcd'));
    }

    public function testReturnsFalseWithNoSig()
    {
        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction(), new Flags(0));
        $this->assertFalse($i->isDefinedHashtypeSignature(new Buffer()));
    }

    public function testIsDefinedHashType()
    {
        $valid = [
            1,
            2,
            3,
            0x81,
            0x82,
            0x83
        ];

        $invalid = [
            4,
            50,
            255
        ];

        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction(), new Flags(0));
        foreach ($valid as $t) {
            $t = new Buffer(chr($t));
            $this->assertTrue($i->isDefinedHashtypeSignature($t));
        }

        foreach ($invalid as $t) {
            $t = new Buffer(chr($t));
            $this->assertFalse($i->isDefinedHashtypeSignature($t));
        }
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\ScriptRuntimeException
     * @expectedExceptionMessage Signature with invalid hashtype
     */
    public function testCheckSignatureEncodingInvalidHashtype()
    {
        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction(), new Flags(InterpreterInterface::VERIFY_STRICTENC));
        $buffer = Buffer::hex('3044022029ff6008e57d80619edf3b03b9a69ae1f8a659d9c231cde629c22f97d5bbf7e702200362617c577aa586fca20348f55a59f5ba71f3d6839b66fcfe13a84749b776e891');

        $i->checkSignatureEncoding($buffer);
        $this->assertTrue(true);
    }

    public function testCheckSignatureSafeWhenFlagNotSet()
    {
        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction(), new Flags(0));
        $buffer = new Buffer('obviously incorrect.....?');
        try {
            $i->checkSignatureEncoding($buffer);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\ScriptRuntimeException
     * @expectedExceptionMessage Signature s element was not low
     */
    public function testCheckSignatureEncodingLowS()
    {
        $i = new Interpreter(Bitcoin::getEcAdapter(), new Transaction(), new Flags(InterpreterInterface::VERIFY_LOW_S));
        $buffer = Buffer::hex('30450220377bf4cab9bbdb219f1b0cca56f4a39fbf787d6fa9d04e248101d498de991d30022100b8e0c72dfab9a0d88eb2703c62e0e57ab2cb906e8f156b7641c2f0e24b8bba2b01');
        $i->checkSignatureEncoding($buffer);
    }

    public function testCheckEmptySignatureSafeWhenFlagNotSet()
    {
        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction(), new Flags(0));
        $buffer = new Buffer('');
        try {
            $i->checkSignatureEncoding($buffer);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\ScriptRuntimeException
     * @expectedExceptionMessage Signature with incorrect encoding
     */
    public function testCheckSignatureEncodingWhenFlagSet()
    {
        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction(), new Flags(InterpreterInterface::VERIFY_DERSIG));
        $buffer = new Buffer('obviously incorrect.....?');
        $i->checkSignatureEncoding($buffer);
    }

    public function testCheckSignatureEncodingWhenLowSFlagSet()
    {
        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction(), new Flags(InterpreterInterface::VERIFY_LOW_S));
        $buffer = Buffer::hex('3044022029ff6008e57d80619edf3b03b9a69ae1f8a659d9c231cde629c22f97d5bbf7e702200362617c577aa586fca20348f55a59f5ba71f3d6839b66fcfe13a84749b776e801');
        try {
            $i->checkSignatureEncoding($buffer);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    public function testCheckSignatureEncodingWhenStrictEncFlagSet()
    {
        $i = new Interpreter(Bitcoin::getEcAdapter(), new Transaction(), new Flags(InterpreterInterface::VERIFY_STRICTENC));
        $buffer = Buffer::hex('3044022029ff6008e57d80619edf3b03b9a69ae1f8a659d9c231cde629c22f97d5bbf7e702200362617c577aa586fca20348f55a59f5ba71f3d6839b66fcfe13a84749b776e801');
        try {
            $i->checkSignatureEncoding($buffer);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    public function testCheckPublicKeyEncoding()
    {
        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction(), new Flags(InterpreterInterface::VERIFY_STRICTENC));
        $pubkey = Buffer::hex('045e9392308b08d0d663961463b6cd056a66b757a2ced9dde197c21362360237f231b80ea66315898969f5c079f0ba3fc1c0661ed8c853ad15043f22f2b7779c95');
        try {
            $i->checkPublicKeyEncoding($pubkey);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\ScriptRuntimeException
     * @expectedExceptionMessage Public key with incorrect encoding
     */
    public function testCheckPublicKeyEncodingFail()
    {
        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction(), new Flags(\BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface::VERIFY_STRICTENC));
        $pubkey = Buffer::hex('045e9392308b08d0d663961463b6cd056a66b757a2ced9dde197c21362360237f231b80ea66315898969f5c079f0ba3fc1c0661ed8c853ad15043f22b7779c95');
        $i->checkPublicKeyEncoding($pubkey);
    }

    /**
     * Construct general test vectors to exercise Checksig operators.
     * Given an output script (limited to those signable by TransactionBuilder), RedeemScript if required,
     * and private key, the tests can attempt to spend a fake transaction. the scriptSig it produces should
     * return true against the given outputScript/redeemScript
     *
     * @return array
     */
    public function ChecksigVectors()
    {
        $ec = EcAdapterFactory::getAdapter(new Math(), EccFactory::getSecgCurves()->generator256k1());
        $privateKey = PrivateKeyFactory::fromHex('4141414141414141414141414141414141414141414141414141414141414141', false, $ec);

        $consensus = new ConsensusFactory($ec);
        $standard = $consensus->defaultFlags();

        $vectors = [];

        // Pay to pubkey hash that succeeds
        $s0 = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPublicKey());
        $vectors[] = [
            true,
            $ec,
            $standard, // flags
            $privateKey,        // privKey
            $s0,
            null,               // redeemscript,
        ];

        // Pay to pubkey that succeeds
        $s1 = ScriptFactory::create()->push($privateKey->getPublicKey()->getBuffer())->op('OP_CHECKSIG');
        $vectors[] = [
            true,
            $ec,
            $standard, // flags
            $privateKey,        // privKey
            $s1,
            null,               // redeemscript
        ];

        $rs = ScriptFactory::multisig(1, [$privateKey->getPublicKey()]);
        $vectors[] = [
            true,
            $ec,
            $standard,
            $privateKey,
            $rs->getOutputScript(),
            $rs,
        ];

        return $vectors;
    }

    /**
     * @dataProvider ChecksigVectors
     */
    public function testChecksigVectors($eVerifyResult, EcAdapterInterface $ec, Flags $flags, PrivateKeyInterface $privateKey, ScriptInterface $outputScript, RedeemScript $rs = null)
    {
        // Create a fake tx to spend - an output script we supposedly can spend.
        $fake = new TransactionBuilder($ec);
        $fake->addOutput(new TransactionOutput(1, $outputScript));

        // Here is where
        $spend = new TransactionBuilder($ec);
        $spend->spendOutput($fake->getTransaction(), 0);
        $spend->signInputWithKey($privateKey, $outputScript, 0, $rs);

        $spendTx = $spend->getTransaction();
        $scriptSig = $spendTx->getInputs()->getInput(0)->getScript();

        $i = new Interpreter($ec, $spendTx, $flags);
        $this->assertEquals($eVerifyResult, $i->verify($scriptSig, $outputScript, 0));
    }

    public function getScripts()
    {
        $f = file_get_contents(__DIR__ . '/../../Data/scriptinterpreter.simple.json');
        $json = json_decode($f);

        $vectors = [];
        foreach ($json->test as $c => $test) {
            $flags = $this->setFlags($test->flags);
            $scriptSig = ScriptFactory::fromHex($test->scriptSig);
            $scriptPubKey = ScriptFactory::fromHex($test->scriptPubKey);
            $vectors[] = [
                $flags, $scriptSig, $scriptPubKey, $test->result, $test->desc, new Transaction
            ];
        }

        $flags = new Flags(0);
        $vectors[] = [
            $flags,
            new Script(new Buffer()),
            ScriptFactory::create()->push(Buffer::hex(file_get_contents(__DIR__ . "/../../Data/10010bytes.hex"))),
            false,
            'fails with >10000 bytes',
            new Transaction
        ];

        return $vectors;
    }


    /**
     * @dataProvider getScripts
     * @param Flags $flags
     * @param ScriptInterface $scriptSig
     * @param ScriptInterface $scriptPubKey
     */
    public function testScript(Flags $flags, ScriptInterface $scriptSig, ScriptInterface $scriptPubKey, $result, $description, $tx)
    {
        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), $tx, $flags);

        $i->setScript($scriptSig)->run();
        $testResult = $i->setScript($scriptPubKey)->run();

        $this->assertEquals($result, $testResult, $description);
    }/**/


    public function testVerifyOnScriptSigFail()
    {
        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction, new Flags(0));
        $script = new Script();
        $script->op('OP_RETURN');

        $this->assertFalse($i->verify($script, new Script, 0));
    }

    public function testVerifyOnScriptPubKeyFail()
    {
        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction, new Flags(0));
        $true = new Script(Buffer::hex('0101'));
        $false = new Script();
        $false->op('OP_RETURN');
        $this->assertFalse($i->verify($true, $false, 0));
    }

    public function testVerifyEmptyAfterExec()
    {
        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction, new Flags(0));
        $empty = new Script();
        $this->assertFalse($i->verify($empty, $empty, 0));
    }

    public function testVerifyNotFalse()
    {
        $true = new Script(Buffer::hex('0101'));
        $false = new Script(Buffer::hex('0100'));

        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction, new Flags(0));
        $this->assertFalse($i->verify($true, $false, 0));
    }

    public function testP2shwithEmptyStack()
    {
        $p2sh = new Script();
        $output = ScriptFactory::scriptPubKey()->payToScriptHash($p2sh);
        $scriptSig = new Script();

        $i = new Interpreter(Bitcoin::getEcAdapter(), new Transaction, new Flags(InterpreterInterface::VERIFY_P2SH));
        $this->assertFalse($i->verify($scriptSig, $output, 0));
    }

    public function testInvalidPayToScriptHash()
    {
        $p2sh = new Script();
        $p2sh->op('OP_RETURN');

        $scriptPubKey = ScriptFactory::scriptPubKey()->payToScriptHash($p2sh);

        $scriptSig = new Script();
        $scriptSig->op('OP_0')->push($p2sh->getBuffer());

        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction, new Flags(InterpreterInterface::VERIFY_P2SH));
        $this->assertFalse($i->verify($scriptSig, $scriptPubKey, 0));
    }


    public function testVerifyScriptsigMustBePushOnly()
    {
        $p2sh = new Script();
        $p2sh->op('OP_1')->push(Buffer::hex('41414141'))->op('OP_DEPTH');

        $scriptSig = new Script();
        $scriptSig->op('OP_DEPTH')->push($p2sh->getBuffer());

        $scriptPubKey = ScriptFactory::scriptPubKey()->payToScriptHash($p2sh);

        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction, new Flags(InterpreterInterface::VERIFY_P2SH));
        $this->assertFalse($i->verify($scriptSig, $scriptPubKey, 0));
    }

    public function testCheckMinimalPush()
    {
        $valid = [
            [0, Buffer::hex('')],
            [81, Buffer::hex('01')],
            [79, Buffer::hex('81')],
            [5, Buffer::hex('0102030405')],
            [0x4c, Buffer::hex('', 76)],
            [0x4c, Buffer::hex('', 78)],
            [0x4c, Buffer::hex('', 255)],
            [0x4d, Buffer::hex('', 256)],
            [0x4d, Buffer::hex('', 65535)],
            [0x4e, Buffer::hex('', 65536)],
        ];

        $invalid = [
            [0x81, Buffer::hex('')],
            [01, Buffer::hex('0102030405')],
            [0x4d, Buffer::hex('', 74)],
            [0x4e, Buffer::hex('', 255)],
            [0x4d, Buffer::hex('', 255)]
        ];

        $i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter(Bitcoin::getEcAdapter(), new Transaction, new Flags(InterpreterInterface::VERIFY_P2SH));
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
