<?php


namespace Bitcoin\Tests\Util;

use Bitcoin\Util\Buffer;
use Bitcoin\Crypto\Hash;
use Bitcoin\Crypto\DRBG\HMACDRBG;
use Bitcoin\Key\PrivateKey;

class HMACDRBGTest extends \PHPUnit_Framework_TestCase
{

    public function testCreateHMACDRBG()
    {
        $f = file_get_contents(__DIR__.'/../../Data/hmacdrbg.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {

            $privKey     = new PrivateKey($test->privKey);
            $messageHash = Buffer::hex(Hash::sha256($test->message));
            $entropy     = new Buffer($privKey->serialize() . $messageHash->serialize());
            $drbg        = new HMACDRBG($test->algorithm, $entropy);
            $k           = $drbg->bytes(32);
            $this->assertEquals(strtolower($test->expectedK), strtolower($k->serialize('hex')));
        }
    }

}