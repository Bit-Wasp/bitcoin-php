<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script\Interpreter;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\Interpreter\Checker;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Buffertools\Buffer;

class CheckerTest extends AbstractTestCase
{

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\ScriptRuntimeException
     * @expectedExceptionMessage Signature with invalid hashtype
     */
    public function testCheckSignatureEncodingInvalidHashtype()
    {
        $f = InterpreterInterface::VERIFY_STRICTENC;
        $c = new Checker(Bitcoin::getEcAdapter(), new Transaction(), 0, 0);

        $buffer = Buffer::hex('3044022029ff6008e57d80619edf3b03b9a69ae1f8a659d9c231cde629c22f97d5bbf7e702200362617c577aa586fca20348f55a59f5ba71f3d6839b66fcfe13a84749b776e891');

        $c->checkSignatureEncoding($buffer, $f);
        $this->assertTrue(true);
    }/**/

    public function testCheckSignatureSafeWhenFlagNotSet()
    {
        $f = 0;
        $c = new Checker(Bitcoin::getEcAdapter(), new Transaction(), 0, 0);
        $buffer = new Buffer('obviously incorrect.....?');
        try {
            $c->checkSignatureEncoding($buffer, $f);
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
        $f = InterpreterInterface::VERIFY_LOW_S;
        $c = new Checker(Bitcoin::getEcAdapter(), new Transaction(), 0, 0);
        $buffer = Buffer::hex('30450220377bf4cab9bbdb219f1b0cca56f4a39fbf787d6fa9d04e248101d498de991d30022100b8e0c72dfab9a0d88eb2703c62e0e57ab2cb906e8f156b7641c2f0e24b8bba2b01');
        $c->checkSignatureEncoding($buffer, $f);
    }

    public function testCheckEmptySignatureSafeWhenFlagNotSet()
    {
        $f = 0;
        $c = new Checker(Bitcoin::getEcAdapter(), new Transaction(), 0, 0);
        $buffer = new Buffer('');
        try {
            $c->checkSignatureEncoding($buffer, $f);
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
        $f = InterpreterInterface::VERIFY_DERSIG;
        $c = new Checker(Bitcoin::getEcAdapter(), new Transaction(), 0, 0);
        $buffer = new Buffer('obviously incorrect.....?');
        $c->checkSignatureEncoding($buffer, $f);
    }

    public function testCheckSignatureEncodingWhenLowSFlagSet()
    {
        $f = InterpreterInterface::VERIFY_LOW_S;
        $c = new Checker(Bitcoin::getEcAdapter(), new Transaction(), 0, 0);
        $buffer = Buffer::hex('3044022029ff6008e57d80619edf3b03b9a69ae1f8a659d9c231cde629c22f97d5bbf7e702200362617c577aa586fca20348f55a59f5ba71f3d6839b66fcfe13a84749b776e801');
        try {
            $c->checkSignatureEncoding($buffer, $f);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    public function testCheckSignatureEncodingWhenStrictEncFlagSet()
    {
        $f = InterpreterInterface::VERIFY_STRICTENC;
        $c = new Checker(Bitcoin::getEcAdapter(), new Transaction(), 0, 0);
        $buffer = Buffer::hex('3044022029ff6008e57d80619edf3b03b9a69ae1f8a659d9c231cde629c22f97d5bbf7e702200362617c577aa586fca20348f55a59f5ba71f3d6839b66fcfe13a84749b776e801');
        try {
            $c->checkSignatureEncoding($buffer, $f);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    public function testCheckPublicKeyEncoding()
    {
        $f = InterpreterInterface::VERIFY_STRICTENC;
        $c = new Checker(Bitcoin::getEcAdapter(), new Transaction(), 0, 0);
        $pubkey = Buffer::hex('045e9392308b08d0d663961463b6cd056a66b757a2ced9dde197c21362360237f231b80ea66315898969f5c079f0ba3fc1c0661ed8c853ad15043f22f2b7779c95');
        try {
            $c->checkPublicKeyEncoding($pubkey, $f);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\ScriptRuntimeException
     * @expectedExceptionMessage Signature with incorrect encoding
     */
    public function testIsLowDERFailsWithIncorrectEncoding()
    {
        $checker = new Checker(Bitcoin::getEcAdapter(), new Transaction(), 0, 0);
        $checker->isLowDerSignature(new Buffer('abcd'));
    }

    public function testReturnsFalseWithNoSig()
    {
        $checker = new Checker(Bitcoin::getEcAdapter(), new Transaction(), 0, 0);
        $this->assertFalse($checker->isDefinedHashtypeSignature(new Buffer()));
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

        $checker = new Checker(Bitcoin::getEcAdapter(), new Transaction, 0, 0);
        foreach ($valid as $t) {
            $t = new Buffer(chr($t));
            $this->assertTrue($checker->isDefinedHashtypeSignature($t));
        }

        foreach ($invalid as $t) {
            $t = new Buffer(chr($t));
            $this->assertFalse($checker->isDefinedHashtypeSignature($t));
        }
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\ScriptRuntimeException
     * @expectedExceptionMessage Public key with incorrect encoding
     */
    public function testCheckPublicKeyEncodingFail()
    {
        $f = InterpreterInterface::VERIFY_STRICTENC;
        $c = new Checker(Bitcoin::getEcAdapter(), new Transaction(), 0, 0);
        $pubkey = Buffer::hex('045e9392308b08d0d663961463b6cd056a66b757a2ced9dde197c21362360237f231b80ea66315898969f5c079f0ba3fc1c0661ed8c853ad15043f22b7779c95');
        $c->checkPublicKeyEncoding($pubkey, $f);
    }
}
