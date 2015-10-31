<?php

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter;

use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use Symfony\Component\Yaml\Yaml;

class EcAdapterTest extends AbstractTestCase
{
    /**
     * @var string
     */
    public $sigType = 'BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface';

    /**
     * @return array
     */
    public function getPrivVectors()
    {
        $datasets = [];
        $yaml = new Yaml();

        $data = $yaml->parse(file_get_contents(__DIR__ . '/../../Data/privateKeys.yml'));
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
        $priv = PrivateKeyFactory::fromHex($privHex, false, $ec);
        $this->assertSame($priv->getPublicKey()->getHex(), $pubHex);

        $priv = PrivateKeyFactory::fromHex($privHex, true, $ec);
        $this->assertSame($priv->getPublicKey()->getHex(), $compressedHex);
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
        $f    = file_get_contents(__DIR__.'/../../Data/publickey.compressed.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            try {
                PublicKeyFactory::fromHex($test->compressed, $ecAdapter);
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
        $f = file_get_contents(__DIR__.'/../../Data/hmacdrbg.json');
        $json = json_decode($f);
        $math = $ecAdapter->getMath();
        foreach ($json->test as $c => $test) {
            $privateKey = PrivateKeyFactory ::fromHex($test->privKey, false, $ecAdapter);
            $message = new Buffer($test->message, null, $math);
            $messageHash = Hash::sha256($message);

            $k = new Rfc6979($ecAdapter, $privateKey, $messageHash);
            $sig = $ecAdapter->sign($messageHash, $privateKey, $k);

            // K must be correct (from privatekey and message hash)
            $this->assertEquals(strtolower($test->expectedK), $k->bytes(32)->getHex());

            // R and S should be correct
            $rHex = $math->dechex($sig->getR());
            $sHex = $math->decHex($sig->getS());
            $this->assertSame($test->expectedRSLow, $rHex . $sHex);

            $this->assertTrue($ecAdapter->verify($messageHash, $privateKey->getPublicKey(), $sig));
        }
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public function testPrivateKeySign(EcAdapterInterface $ecAdapter)
    {
        $random = new Random();
        $pk = $ecAdapter->getPrivateKey('4141414141414141414141414141414141414141414141414141414141414141', false);

        $hash = $random->bytes(32);
        $sig = $ecAdapter->sign($hash, $pk, new Random());

        $this->assertInstanceOf($this->sigType, $sig);
        $this->assertTrue($ecAdapter->verify($hash, $pk->getPublicKey(), $sig));
    }
}
