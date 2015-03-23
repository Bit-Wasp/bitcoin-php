<?php

namespace BitWasp\Bitcoin\Tests\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Signature\Signature;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

/**
 * Class SignatureTest
 * @package Bitcoin
 */
class SignerTest extends AbstractTestCase
{
    /**
     * @var string
     */
    public $sigType = 'BitWasp\Bitcoin\Signature\Signature';

    /**
     * @var \BitWasp\Bitcoin\Math\Math
     */
    public $math;

    /**
     * @var \Mdanter\Ecc\GeneratorPoint
     */
    public $generator;

    public function setUp()
    {
        $this->math = Bitcoin::getMath();
        $this->generator = Bitcoin::getGenerator();
    }

    /**
     * @dataProvider getEcAdapters
     */
    public function testDeterministicSign(EcAdapterInterface $ecAdapter)
    {
        $f = file_get_contents(__DIR__.'/../Data/hmacdrbg.json');
        $json = json_decode($f);

        foreach ($json->test as $c => $test) {
            $privateKey = PrivateKeyFactory::fromHex($test->privKey, false, $ecAdapter);
            $message = new Buffer($test->message);
            $messageHash = new Buffer(Hash::sha256($message->serialize(), true));

            $k = new Rfc6979($this->math, $this->generator, $privateKey, $messageHash);
            $sig = $ecAdapter->sign($privateKey, $messageHash, $k);

            // K must be correct (from privatekey and message hash)
            $this->assertEquals(Buffer::hex($test->expectedK), $k->bytes(32));

            // R and S should be correct
            $rHex = $this->math->dechex($sig->getR());
            $sHex = $this->math->decHex($sig->getS());
            $this->assertSame($test->expectedRSLow, $rHex.$sHex);
        }
    }

    /*public function testHaskoinDeterministicSign()
    {

         $f = file_get_contents(__DIR__.'/../Data/haskoin.sigtests.json');

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
        }
    }*/

    /**
     * @dataProvider getEcAdapters
     */
    public function testPrivateKeySign(EcAdapterInterface $ecAdapter)
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
        $pk = PrivateKeyFactory::fromInt('4141414141414141414141414141414141414141414141414141414141414141', false, $ecAdapter);

        for ($i = 0; $i < 2; $i++) {
            $hash = $random->bytes(32);
            $sig = $ecAdapter->sign($pk, $hash, new Random());

            $this->assertInstanceOf($this->sigType, $sig);
            $this->assertTrue(Signature::isDERSignature($sig->getBuffer()));
            $this->assertTrue($ecAdapter->verify($pk->getPublicKey(), $sig, $hash));
        }
    }
}
