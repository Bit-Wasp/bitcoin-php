<?php

namespace Bitcoin\Tests\Util;

use Bitcoin\Crypto\Hash;
use Bitcoin\Util\Buffer;

class HashTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Hash
     */
    protected $hash;

    public function setUp()
    {
        $this->hash = new Hash();
    }

    public function testNormalize()
    {
        $data = Buffer::hex('414141');
        $this->assertSame(hex2bin('414141'), $this->hash->normalize($data));
    }

    public function testSha256()
    {
        $f = file_get_contents(__DIR__.'/../Data/hash.sha256.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $hash = $this->hash->sha256($test->data);
            $this->assertSame($hash, $test->result);
        }
    }

    public function testSha256d()
    {
        $f = file_get_contents(__DIR__.'/../Data/hash.sha256d.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $hash = $this->hash->sha256d($test->data);
            $this->assertSame($hash, $test->result);
        }
    }

    public function testRipemd160()
    {
        $f = file_get_contents(__DIR__.'/../Data/hash.ripemd160.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $hash = $this->hash->ripemd160($test->data);
            $this->assertSame($hash, $test->result);
        }
    }

    public function testRipemd160d()
    {
        $f = file_get_contents(__DIR__.'/../Data/hash.ripemd160d.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $hash = $this->hash->ripemd160d($test->data);
            $this->assertSame($hash, $test->result);
        }
    }

    public function testPBKDF2()
    {
        $f = file_get_contents(__DIR__.'/../Data/hash.pbkdf2.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $hash = $this->hash->pbkdf2($test->algo, $test->data, $test->salt, $test->iterations, $test->length);
            $this->assertSame($hash, $test->result);
        }
    }

    public function testPBKDF2InternalConsistent()
    {
        $f = file_get_contents(__DIR__.'/../Data/hash.pbkdf2.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {

            $hash1 = $this->hash->pbkdf2Pure($test->algo, $test->data, $test->salt, $test->iterations, $test->length / 2);
            $this->assertSame($hash1, $test->result);

            $hash1 = $this->hash->pbkdf2Pure($test->algo, $test->data, $test->salt, $test->iterations, $test->length / 2, true);
            $this->assertSame($hash1, hex2bin($test->result));

            if (function_exists('hash_pbkdf2')) {
                $hash = $this->hash->pbkdf2Extension($test->algo, $test->data, $test->salt, $test->iterations, $test->length / 2);
                $this->assertSame($hash, $test->result);
            }


        }
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage PBKDF2 ERROR: Invalid hash algorithm
     */
    public function testPbkdf2FailsInvalidAlgorithm()
    {
        $this->hash->pbkdf2('test', 'password', 'salt', 100, 128);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage PBKDF2 ERROR: Invalid parameters
     */
    public function testPbkdf2FailsInvalidCount()
    {
        $this->hash->pbkdf2('sha512', 'password', 'salt', 0, 128);
    }

    public function testSha256Ripe160()
    {
        $f = file_get_contents(__DIR__.'/../Data/hash.sha256ripe160.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $hash = $this->hash->sha256ripe160($test->data);
            $this->assertSame($hash, $test->result);
        }

    }
    public function testSha1()
    {
        $f = file_get_contents(__DIR__.'/../Data/hash.sha1.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $hash = $this->hash->sha1($test->data);
            $this->assertSame($hash, $test->result);
        }

    }
}
 