<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Crypto;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class HashTest extends AbstractTestCase
{
    public function testSha256()
    {
        $json = json_decode($this->dataFile('hash.sha256.json'));
        foreach ($json->test as $test) {
            $this->assertSame($test->result, Hash::sha256(new Buffer($test->data))->getHex());
        }
    }

    public function testSha256d()
    {
        $json = json_decode($this->dataFile('hash.sha256d.json'));
        foreach ($json->test as $test) {
            $this->assertSame($test->result, Hash::sha256d(new Buffer($test->data))->getHex());
        }
    }

    public function testRipemd160()
    {
        $json = json_decode($this->dataFile('hash.ripemd160.json'));
        foreach ($json->test as $test) {
            $this->assertSame($test->result, Hash::ripemd160(new Buffer($test->data))->getHex());
        }
    }

    public function testRipemd160d()
    {
        $json = json_decode($this->dataFile('hash.ripemd160d.json'));
        foreach ($json->test as $test) {
            $this->assertSame($test->result, Hash::ripemd160d(new Buffer($test->data))->getHex());
        }
    }

    public function testPBKDF2()
    {
        $json = json_decode($this->dataFile('hash.pbkdf2.json'));
        foreach ($json->test as $test) {
            $hash = Hash::pbkdf2($test->algo, new Buffer($test->data), new Buffer($test->salt), $test->iterations, $test->length);
            $this->assertSame($test->result, $hash->getHex());
        }
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage PBKDF2 ERROR: Invalid hash algorithm
     */
    public function testPbkdf2FailsInvalidAlgorithm()
    {
        Hash::pbkdf2('test', new Buffer('password'), new Buffer('salt'), 100, 128);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage PBKDF2 ERROR: Invalid parameters
     */
    public function testPbkdf2FailsInvalidCount()
    {
        Hash::pbkdf2('sha512', new Buffer('password'), new Buffer('salt'), 0, 128);
    }

    public function testSha256Ripe160()
    {
        $json = json_decode($this->dataFile('hash.sha256ripe160.json'));
        foreach ($json->test as $test) {
            $this->assertSame($test->result, Hash::sha256ripe160(Buffer::hex($test->data))->getHex());
        }
    }

    public function testSha1()
    {
        $json = json_decode($this->dataFile('hash.sha1.json'));
        foreach ($json->test as $test) {
            $this->assertSame($test->result, Hash::sha1(new Buffer($test->data))->getHex());
        }
    }
}
