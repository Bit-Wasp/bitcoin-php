<?php

namespace Afk11\Bitcoin\Tests\Signature;

use Afk11\Bitcoin\Exceptions\SignatureNotCanonical;
use Afk11\Bitcoin\Key\PrivateKey;
use Afk11\Bitcoin\Crypto\Random\Random;
use Afk11\Bitcoin\Key\PrivateKeyFactory;
use Afk11\Bitcoin\Signature\Signature;
use Afk11\Bitcoin\Signature\Signer;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Crypto\Hash;

/**
 * Class SignatureTest
 * @package Bitcoin
 */
class SignerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    public $sigType;

    public function __construct()
    {
        $this->sigType = 'Afk11\Bitcoin\Signature\Signature';
        $this->math = Bitcoin::getMath();
        $this->generator = Bitcoin::getGenerator();
    }


    public function testDeterministicSign()
    {

        $f = file_get_contents(__DIR__.'/../Data/hmacdrbg.json');

        $json = json_decode($f);

        $signer = new \Afk11\Bitcoin\Signature\Signer($this->math, $this->generator);

        foreach ($json->test as $c => $test) {
            $privateKey = PrivateKeyFactory::fromHex($test->privKey, false, $this->math, $this->generator);
            $message = new Buffer($test->message);
            $messageHash = new Buffer(Hash::sha256($message->serialize(), true));

            $k = new \Afk11\Bitcoin\Crypto\Random\Rfc6979($this->math, $this->generator, $privateKey, $messageHash);
            $sig = $signer->sign($privateKey, $messageHash, $k);

            // K must be correct (from privatekey and message hash)
            $this->assertEquals(Buffer::hex($test->expectedK), $k->bytes(32));

            // R and S should be correct
            $rHex = $this->math->dechex($sig->getR());
            $sHex = $this->math->decHex($sig->getS());
            $this->assertSame($test->expectedRSLow, $rHex.$sHex);
        }
    }

    public function testHaskoinDeterministicSign()
    {

        /* $f = file_get_contents(__DIR__.'/../Data/haskoin.sigtests.json');

        $json = json_decode($f);
        $math = Bitcoin::getMath();
        $generator = Bitcoin::getGenerator();
        $signer = new \Bitcoin\Signature\Signer($math, $generator);

        foreach ($json->test as $c => $test) {

            $privateKey = new PrivateKey($test->privKey);
            $message = new Buffer($test->message);
            $messageHash = new Buffer(Hash::sha256($message->serialize(), true));

            $k = new \Bitcoin\Signature\K\DeterministicK($privateKey, $messageHash);
            $sig = $signer->sign($privateKey, $messageHash, $k);

            // K must be correct (from privatekey and message hash)
          //  $this->assertEquals(Buffer::hex($test->expectedK), $k->getK());

            // R and S should be correct
            $rHex = $math->dechex($sig->getR());
            $sHex = $math->decHex($sig->getS());
            $this->assertSame($test->expectedRSLow, $rHex.$sHex);
        }*/
    }

    public function testPrivateKeySign()
    {
        /**
         * This looks enough times to try and catch some of the outliers..
         * - Odd length hex strings need to be padded with one single '0' value,
         *   which happens on occasion when converting from decimal to hex.
         * - Padding also must be applied to prevent r and s from being negative.
         * Signature lengths vary with a certain probability, but the most annoying
         * thing while writing this test was cases where r / s were 31.5 bytes.
         * Should be at least 100 to catch these, but it can take a while
         */
        $random = new Random();

        $signer = new Signer($this->math, $this->generator);
        $pk = PrivateKeyFactory::fromInt('4141414141414141414141414141414141414141414141414141414141414141', false, $this->math, $this->generator);

        for ($i = 0; $i < 2; $i++) {
            $hash = $random->bytes(32);
            $sig = $signer->sign($pk, $hash, new Random());

            $this->assertInstanceOf($this->sigType, $sig);
            $this->assertTrue(Signature::isDERSignature(new Buffer($sig->serialize())));
            $this->assertTrue($signer->verify($pk->getPublicKey(), $hash, $sig));
        }
    }
}
