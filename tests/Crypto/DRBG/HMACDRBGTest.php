<?php


namespace Bitcoin\Tests\Util;

use Bitcoin\Bitcoin;
use Bitcoin\Util\Buffer;
use Bitcoin\Crypto\Hash;
use Bitcoin\Crypto\DRBG\HMACDRBG;
use Bitcoin\Key\PrivateKey;

class HMACDRBGTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateNew()
    {
        $drbg = new HMACDRBG('sha256', Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141'));
        $this->assertInstanceOf('Bitcoin\Crypto\DRBG\HMACDRBG', $drbg);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCreateInvalidAlgorithm()
    {
        $drbg = new HMACDRBG('fake', Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141'));
        $this->assertInstanceOf('Bitcoin\Crypto\DRBG\HMACDRBG', $drbg);
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
            $drbg        = new HMACDRBG($test->algorithm, $entropy);
            $k           = $drbg->bytes(32);
            $this->assertEquals(strtolower($test->expectedK), strtolower($k->serialize('hex')));
        }
    }

}