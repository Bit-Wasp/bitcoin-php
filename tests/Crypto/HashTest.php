<?php

namespace BitWasp\Bitcoin\Tests\Crypto;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Buffertools\Buffer;

class HashTest extends \PHPUnit_Framework_TestCase
{
    public function testSha256()
    {
        $f = file_get_contents(__DIR__.'/../Data/hash.sha256.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $this->assertSame($test->result, Hash::sha256(new Buffer($test->data))->getHex());
        }
    }

    public function testSha256d()
    {
        $f = file_get_contents(__DIR__.'/../Data/hash.sha256d.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $this->assertSame($test->result, Hash::sha256d(new Buffer($test->data))->getHex());
        }
    }

    public function testRipemd160()
    {
        $f = file_get_contents(__DIR__.'/../Data/hash.ripemd160.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $this->assertSame($test->result, Hash::ripemd160(new Buffer($test->data))->getHex());
        }
    }

    public function testRipemd160d()
    {
        $f = file_get_contents(__DIR__.'/../Data/hash.ripemd160d.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $this->assertSame($test->result, Hash::ripemd160d(new Buffer($test->data))->getHex());
        }
    }

    public function testPBKDF2()
    {
        $f = file_get_contents(__DIR__.'/../Data/hash.pbkdf2.json');

        $json = json_decode($f);
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
        $f = file_get_contents(__DIR__.'/../Data/hash.sha256ripe160.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $this->assertSame($test->result, Hash::sha256ripe160(Buffer::hex($test->data))->getHex());
        }

    }
    public function testSha1()
    {
        $f = file_get_contents(__DIR__.'/../Data/hash.sha1.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $this->assertSame($test->result, Hash::sha1(new Buffer($test->data))->getHex());
        }
    }
}
