<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 20/11/14
 * Time: 15:00
 */

namespace Bitcoin;

class Base58Test extends \PHPUnit_Framework_TestCase
{

    protected $base58;

    public function setUp()
    {
        $this->base58 = new Base58();
    }

    public function testEncode()
    {

        $f = file_get_contents(__DIR__.'/../Data/base58.encodedecode.json');

        $json = json_decode($f);

        foreach ($json->test as $test) {
            $hash = Base58::encode($test[0]);
            $this->assertSame($hash, $test[1]);
        }

    }
}
 