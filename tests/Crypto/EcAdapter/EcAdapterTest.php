<?php

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Signature\Signature;

class EcAdapterTest extends AbstractTestCase
{
    /**
     * @var string
     */
    public $sigType = 'BitWasp\Bitcoin\Signature\Signature';


    //
    public function testRecoverYfromX()
    {
        $ecAdapter = Bitcoin::getEcAdapter();
        $math = $ecAdapter->getMath();

        $f = file_get_contents(__DIR__.'/../../Data/publickey.compressed.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $byte = substr($test->compressed, 0, 2);
            $x    = $math->hexDec(substr($test->compressed, 2, 64));
            $realy= $math->hexDec(substr($test->uncompressed, 66, 64));
            $y    = $ecAdapter->recoverYfromX($x, $byte);
            $this->assertSame($realy, $y);
        }
    }

    /**
     * @dataProvider getEcAdapters
     */
    public function testIsValidKey(EcAdapterInterface $ecAdapter)
    {
        // Keys must be < the order of the curve
        // Order of secp256k1 - 1
        $valid = [
            'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364140',
            '4141414141414141414141414141414141414141414141414141414141414141',
            '8000000000000000000000000000000000000000000000000000000000000000',
            '8000000000000000000000000000000000000000000000000000000000000001'
        ];

        foreach ($valid as $key) {
            $key = Buffer::hex($key);
            $this->assertTrue($ecAdapter->validatePrivateKey($key));
        }

        $invalid = [
            'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141',
            '0000000000000000000000000000000000000000000000000000000000000000'
        ];

        foreach ($invalid as $key) {
            $key = Buffer::hex($key);
            $this->assertFalse($ecAdapter->validatePrivateKey($key));
        }

    }

    /**
     * @dataProvider getEcAdapters
     */
    public function testIsValidPublicKey(EcAdapterInterface $ecAdapter)
    {
        $f    = file_get_contents(__DIR__.'/../../Data/publickey.compressed.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $key = Buffer::hex($test->compressed);
            $this->assertTrue($ecAdapter->validatePublicKey($key));
        }
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testDeterministicSign(EcAdapterInterface $ecAdapter)
    {
        $f = file_get_contents(__DIR__.'/../../Data/hmacdrbg.json');
        $json = json_decode($f);

        foreach ($json->test as $c => $test) {
            $privateKey = PrivateKeyFactory ::fromHex($test->privKey, false, $ecAdapter);
            $message = new Buffer($test->message);
            $messageHash = new Buffer(Hash::sha256($message->serialize(), true));

            $k = new Rfc6979 ($ecAdapter->getMath(), $ecAdapter->getGenerator(), $privateKey, $messageHash);
            $sig = $ecAdapter->sign($privateKey, $messageHash, $k);

            // K must be correct (from privatekey and message hash)
            $this->assertEquals(Buffer::hex($test->expectedK), $k->bytes(32));

            // R and S should be correct
            $rHex = $ecAdapter->getMath()->dechex($sig->getR());
            $sHex = $ecAdapter->getMath()->decHex($sig->getS());
            $this->assertSame($test->expectedRSLow, $rHex . $sHex);

            $this->assertTrue($ecAdapter->verify($privateKey->getPublicKey(), $sig, $messageHash));
        }
    }

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