<?php


namespace Afk11\Bitcoin\Tests\Crypto\Random;

use \Afk11\Bitcoin\Bitcoin;
use \Afk11\Bitcoin\Buffer;
use \Afk11\Bitcoin\Crypto\Hash;
use \Afk11\Bitcoin\Crypto\Random\HmacDrbg;
use \Afk11\Bitcoin\Key\PrivateKey;

class HMACDRBGTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateNew()
    {
        $drbg = new HmacDrbg('sha256', Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141'));
        $this->assertInstanceOf('Bitcoin\Crypto\Random\HMACDRBG', $drbg);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCreateInvalidAlgorithm()
    {
        $drbg = new HmacDrbg('fake', \Afk11\Bitcoin\Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141'));
        $this->assertInstanceOf('Bitcoin\Crypto\Random\HMACDRBG', $drbg);
    }

    public function testCreateHMACDRBG()
    {
        $f = file_get_contents(__DIR__.'/../../Data/hmacdrbg.json');
        $math = Bitcoin::getMath();
        $generator = Bitcoin::getGenerator();

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $privKey     = new PrivateKey($math, $generator, $test->privKey);
            $messageHash = Buffer::hex(Hash::sha256($test->message));
            $entropy     = new Buffer($privKey->serialize() . $messageHash->serialize());
            $drbg        = new HmacDrbg($test->algorithm, $entropy);
            $k           = $drbg->bytes(32);
            $this->assertEquals(strtolower($test->expectedK), strtolower($k->serialize('hex')));
        }
    }
}
