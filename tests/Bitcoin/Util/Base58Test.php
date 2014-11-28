<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 20/11/14
 * Time: 15:00
 */

namespace Bitcoin\Util;

class Base58Test extends \PHPUnit_Framework_TestCase
{

    protected $base58;

    public function setUp()
    {
        $this->base58 = new Base58();
    }

    public function testEncode()
    {

        $f    = file_get_contents(__DIR__.'/../../Data/base58.encodedecode.json');
        $json = json_decode($f);

        foreach ($json->test as $test) {
            $hash = $this->base58->encode($test[0]);
            $this->assertSame($test[1], $hash);
        }

    }

    /**
     * @expectedException \Exception
     */
    public function testEncodeWithException()
    {
        $hash = $this->base58->encode('41414141a');
    }

    public function testEncodeDecode2()
    {

    }
    public function testEncodeDecode()
    {
        $f = file_get_contents(__DIR__.'/../../Data/base58.encodedecode.json');

        $json = json_decode($f);

        foreach ($json->test as $test) {
            $encoded = $this->base58->encode($test[0]);

            $this->assertSame($test[1],$encoded);
            $back = $this->base58->decode($encoded);
            $this->assertSame($test[0], $back);
        }
    }
    public function testWeird()
    {
        $str = '00000000000000000000';
        $encode = $this->base58->encode($str);
        $decode = $this->base58->decode($encode);

        $this->assertSame($encode, '1111111111');
        $this->assertSame($decode, $str);

    }
    public function testEncodeDecodeCheck()
    {
        $f = file_get_contents(__DIR__.'/../../Data/base58.encodedecode.json');

        $json = json_decode($f);

        foreach ($json->test as $test) {
            $encoded = $this->base58->encodeCheck($test[0]);
            $back = $this->base58->decodeCheck($encoded);

            $this->assertSame($test[0], $back);
        }
    }
}
 