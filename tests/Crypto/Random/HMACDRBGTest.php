<?php


namespace BitWasp\Bitcoin\Tests\Crypto\Random;

use \BitWasp\Bitcoin\Bitcoin;
use \BitWasp\Buffertools\Buffer;
use \BitWasp\Bitcoin\Crypto\Hash;
use \BitWasp\Bitcoin\Crypto\Random\HmacDrbg;
use \BitWasp\Bitcoin\Key\PrivateKey;

class HMACDRBGTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateNew()
    {
        $drbg = new HmacDrbg('sha256', Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141'));
        $this->assertInstanceOf('BitWasp\Bitcoin\Crypto\Random\HMACDRBG', $drbg);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCreateInvalidAlgorithm()
    {
        $drbg = new HmacDrbg('fake', \BitWasp\Buffertools\Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141'));
    }

    public function testCreateHMACDRBG()
    {
        $f = file_get_contents(__DIR__.'/../../Data/hmacdrbg.json');
        $math = Bitcoin::getMath();
        $generator = Bitcoin::getGenerator();

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $key = $math->hexDec($test->privKey);
            $privKey     = new PrivateKey(Bitcoin::getEcAdapter(), $key);
            $messageHash = Buffer::hex(Hash::sha256($test->message));
            $entropy     = new Buffer($privKey->getBuffer()->serialize() . $messageHash->serialize());
            $drbg        = new HmacDrbg($test->algorithm, $entropy);
            $k           = $drbg->bytes(32);
            $this->assertEquals(strtolower($test->expectedK), strtolower($k->serialize('hex')));
        }
    }
}
