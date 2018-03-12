<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class EcAdapterTest extends AbstractTestCase
{
    /**
     * @return array
     */
    public function getPrivVectors()
    {
        $datasets = [];
        $data = json_decode($this->dataFile('privateKeys.json'), true);

        foreach ($data['vectors'] as $vector) {
            foreach ($this->getEcAdapters() as $adapter) {
                $datasets[] = [
                    $adapter[0],
                    $vector['priv'],
                    $vector['public'],
                    $vector['compressed']
                ];
            }
        }

        return $datasets;
    }

    /**
     * @dataProvider getPrivVectors
     * @param EcAdapterInterface $ec
     * @param $privHex
     * @param $pubHex
     * @param $compressedHex
     * @throws \Exception
     */
    public function testPrivateToPublic(EcAdapterInterface $ec, $privHex, $pubHex, $compressedHex)
    {
        $ucFactory = PrivateKeyFactory::uncompressed($ec);
        $priv = $ucFactory->fromHex($privHex);
        $this->assertSame($priv->getPublicKey()->getHex(), $pubHex);
        $this->assertSame($privHex, $priv->getHex());

        $cFactory = PrivateKeyFactory::compressed($ec);
        $priv = $cFactory->fromHex($privHex);
        $this->assertSame($compressedHex, $priv->getPublicKey()->getHex());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
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
            $key = Buffer::hex($key, 32);
            $this->assertFalse($ecAdapter->validatePrivateKey($key));
        }
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testIsValidPublicKey(EcAdapterInterface $ecAdapter)
    {
        $json = json_decode($this->dataFile('publickey.compressed.json'));
        $pubKeyFactory = new PublicKeyFactory($ecAdapter);
        foreach ($json->test as $test) {
            try {
                $pubKeyFactory->fromHex($test->compressed, $ecAdapter);
                $valid = true;
            } catch (\Exception $e) {
                $valid = false;
            }
            $this->assertTrue($valid);
        }
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testDeterministicSign(EcAdapterInterface $ecAdapter)
    {
        $json = json_decode($this->dataFile('hmacdrbg.json'));
        $math = $ecAdapter->getMath();
        $ucFactory = PrivateKeyFactory::uncompressed($ecAdapter);
        foreach ($json->test as $c => $test) {
            $privateKey = $ucFactory->fromHex($test->privKey);
            $message = new Buffer($test->message, null);
            $messageHash = Hash::sha256($message);

            $k = new Rfc6979($ecAdapter, $privateKey, $messageHash);
            $sig = $privateKey->sign($messageHash, $k);

            // K must be correct (from privatekey and message hash)
            $this->assertEquals(strtolower($test->expectedK), $k->bytes(32)->getHex());

            // R and S should be correct
            $rHex = $math->decHex(gmp_strval($sig->getR(), 10));
            $sHex = $math->decHex(gmp_strval($sig->getS(), 10));
            $this->assertSame($test->expectedRSLow, $rHex . $sHex);

            $this->assertTrue($privateKey->getPublicKey()->verify($messageHash, $sig));
        }
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testPrivateKeySign(EcAdapterInterface $ecAdapter)
    {
        $random = new Random();
        $pk = $ecAdapter->getPrivateKey(gmp_init('4141414141414141414141414141414141414141414141414141414141414141'), false);

        $hash = $random->bytes(32);
        $sig = $pk->sign($hash, new Random());

        $this->assertInstanceOf(SignatureInterface::class, $sig);
        $this->assertTrue($pk->getPublicKey()->verify($hash, $sig));
    }
}
