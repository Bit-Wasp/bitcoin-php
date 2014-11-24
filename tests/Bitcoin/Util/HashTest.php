<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 20/11/14
 * Time: 14:32
 */

namespace Bitcoin\Util;


class HashTest extends \PHPUnit_Framework_TestCase {

    protected $hash;

    public function setUp()
    {
        $this->hash = new Hash();
    }

    public function testSha256()
    {
        $f = file_get_contents(__DIR__.'/../../Data/hash.sha256.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $hash = $this->hash->sha256($test->data);
            $this->assertSame($hash, $test->result);
        }
    }

    public function testSha256d()
    {
        $f = file_get_contents(__DIR__.'/../../Data/hash.sha256d.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $hash = $this->hash->sha256d($test->data);
            $this->assertSame($hash, $test->result);
        }
    }

    public function testRipemd160()
    {
        $f = file_get_contents(__DIR__.'/../../Data/hash.ripemd160.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $hash = $this->hash->ripemd160($test->data);
            $this->assertSame($hash, $test->result);
        }
    }

    public function testRipemd160d()
    {
        $f = file_get_contents(__DIR__.'/../../Data/hash.ripemd160d.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $hash = $this->hash->ripemd160d($test->data);
            $this->assertSame($hash, $test->result);
        }
    }

    public function testPBKDF2()
    {
        $f = file_get_contents(__DIR__.'/../../Data/hash.pbkdf2.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $hash = $this->hash->pbkdf2($test->algo, $test->data, $test->salt, $test->iterations, $test->length);
            $this->assertSame($hash, $test->result);
        }
    }


    public function testSha256Ripe160()
    {
        $f = file_get_contents(__DIR__.'/../../Data/hash.sha256ripe160.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $hash = $this->hash->sha256ripe160($test->data);
            $this->assertSame($hash, $test->result);
        }

    }
    public function testSha1()
    {
        $f = file_get_contents(__DIR__.'/../../Data/hash.sha1.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $hash = $this->hash->sha1($test->data);
            $this->assertSame($hash, $test->result);
        }

    }
}
 