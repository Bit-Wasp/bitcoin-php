<?php

namespace BitWasp\Bitcoin\Tests\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Key\HierarchicalKey;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class HierarchicalKeyTest extends AbstractTestCase
{
    /**
     * @var Math
     */
    protected $math;
    /**
     *
     * @var HierarchicalKey
     */
    protected $key;

    /**
     * @var Network
     */
    protected $network;

    /**
     * @var string
     */
    protected $baseType = 'BitWasp\Bitcoin\Key\HierarchicalKey';

    /**
     *
     */
    public function setUp()
    {
        $this->math = Bitcoin::getMath();
        $this->key = null;
        $this->network = NetworkFactory::bitcoin();
    }

    /**
     * @param HierarchicalKey $key
     * @param $vectors
     */
    private function compareToPrivVectors(HierarchicalKey $key, $vectors)
    {
        $this->assertSame($vectors->secret_wif, $key->getPrivateKey()->toWif($this->network));
        $this->assertSame($vectors->secret_wif, $key->getPrivateKey()->toWif());
        $this->assertSame($vectors->address, $key->getPrivateKey()->getAddress()->getAddress($this->network));
        $this->assertSame($vectors->address, $key->getPrivateKey()->getAddress()->getAddress());

        $this->assertSame($vectors->xprv_b58, $key->toExtendedPrivateKey($this->network), 'correct xprv');
        $this->assertSame($vectors->xprv_b58, $key->toExtendedPrivateKey(), 'correct xprv');
        $this->assertSame($vectors->xpub_b58, $key->toExtendedPublicKey($this->network), 'correct xpub');
        $this->assertSame($vectors->xpub_b58, $key->toExtendedPublicKey(), 'correct xpub');
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testGenerateNew(EcAdapterInterface $ecAdapter)
    {
        $this->key = HierarchicalKeyFactory::generateMasterKey($ecAdapter);
        $this->assertInstanceOf($this->baseType, $this->key);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage A HierarchicalKey must always be compressed
     */
    public function testFailsWithUncompressed()
    {
        new HierarchicalKey(
            Bitcoin::getEcAdapter(),
            1,
            1,
            1,
            1,
            PrivateKeyFactory::create(false)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDecodePathFailure()
    {
        $key = HierarchicalKeyFactory::generateMasterKey(Bitcoin::getEcAdapter());
        $key->decodePath('');
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testFromEntropy(EcAdapterInterface $ecAdapter)
    {
        $this->key = HierarchicalKeyFactory::fromEntropy('4141414141414141414141414141414141414141414141414141414141414141', $ecAdapter);
        $this->assertInstanceOf($this->baseType, $this->key);
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws \Exception
     */
    public function testTestVectors(EcAdapterInterface $ecAdapter)
    {
        $f = file_get_contents(__DIR__ . '/../Data/bip32testvectors.json');

        $json = json_decode($f);
        foreach ($json->test as $testC => $test) {
            $master = HierarchicalKeyFactory::fromEntropy($test->master, $ecAdapter);
            $this->compareToPrivVectors($master, $test->details);

            $key = clone($master);
            foreach ($test->derivs as $childDeriv)
            {
                $path = $key->decodePath($childDeriv->path);
                $key  = $key->deriveChild($path);

                $this->compareToPrivVectors($key, $childDeriv->details);
            }
        }
    }

    public function testDecodePath() {
        // need to init a HierarchicalKey to be able to call the method :/
        $key = HierarchicalKeyFactory::fromExtended('xprv9s21ZrQH143K24zyWeuwtaWrpNjzYRX9VNSFgT6TwC8aBK46j95aWJM7rW9uek4M9BNosaoN8fLFMi3UVMAynimfuf164nXoZpaQJa2FXpU', $this->network);

        $this->assertEquals("2147483648/2147483649/444/2147526030", $key->decodePath("0'/1'/444/42382'"));
    }

    public function testDerivePath() {
        $masterKey = HierarchicalKeyFactory::fromEntropy("000102030405060708090a0b0c0d0e0f");
        $this->assertEquals("xprv9s21ZrQH143K3QTDL4LXw2F7HEK3wJUD2nW2nRk4stbPy6cq3jPPqjiChkVvvNKmPGJxWUtg6LnF5kejMRNNU3TGtRBeJgk33yuGBxrMPHi", $masterKey->toExtendedKey());

        $firstChildKey = $masterKey->derivePath("0");
        $this->assertEquals("xprv9uHRZZhbkedL37eZEnyrNsQPFZYRAvjy5rt6M1nbEkLSo378x1CQQLo2xxBvREwiK6kqf7GRNvsNEchwibzXaV6i5GcsgyjBeRguXhKsi4R", $firstChildKey->toExtendedKey());

        $bip44ChildKey = $masterKey->derivePath("44'/0'/0'/0/0");
        $this->assertEquals("xprvA4A9CuBXhdBtCaLxwrw64Jaran4n1rgzeS5mjH47Ds8V67uZS8tTkG8jV3BZi83QqYXPcN4v8EjK2Aof4YcEeqLt688mV57gF4j6QZWdP9U", $bip44ChildKey->toExtendedKey());

        // get the "m/44'/0'/0'/0/0" derivation, in 2 steps
        $bip44ChildKey = $masterKey->derivePath("44'/0'");
        $bip44ChildKey = $bip44ChildKey->derivePath("0'/0/0");
        $this->assertEquals("xprvA4A9CuBXhdBtCaLxwrw64Jaran4n1rgzeS5mjH47Ds8V67uZS8tTkG8jV3BZi83QqYXPcN4v8EjK2Aof4YcEeqLt688mV57gF4j6QZWdP9U", $bip44ChildKey->toExtendedKey());

        // get the "m/44'/0'/0'/0/0" derivation, in 2 steps
        $bip44ChildKey = $masterKey->derivePath("44'/0'/0'");
        $bip44ChildKey = $bip44ChildKey->derivePath("0/0");
        $this->assertEquals("xprvA4A9CuBXhdBtCaLxwrw64Jaran4n1rgzeS5mjH47Ds8V67uZS8tTkG8jV3BZi83QqYXPcN4v8EjK2Aof4YcEeqLt688mV57gF4j6QZWdP9U", $bip44ChildKey->toExtendedKey());

        // get the "m/44'/0'/0'/0/0" derivation, in 2 steps
        $bip44ChildKey = $masterKey->derivePath("44'/0'/0'/0");
        $bip44ChildKey = $bip44ChildKey->derivePath("0");
        $this->assertEquals("xprvA4A9CuBXhdBtCaLxwrw64Jaran4n1rgzeS5mjH47Ds8V67uZS8tTkG8jV3BZi83QqYXPcN4v8EjK2Aof4YcEeqLt688mV57gF4j6QZWdP9U", $bip44ChildKey->toExtendedKey());

        // get the "m/44'/0'/0'/0/0" derivation, in single steps
        $bip44ChildKey = $masterKey->derivePath("44'");
        $bip44ChildKey = $bip44ChildKey->derivePath("0'");
        $bip44ChildKey = $bip44ChildKey->derivePath("0'");
        $bip44ChildKey = $bip44ChildKey->derivePath("0");
        $bip44ChildKey = $bip44ChildKey->derivePath("0");
        $this->assertEquals("xprvA4A9CuBXhdBtCaLxwrw64Jaran4n1rgzeS5mjH47Ds8V67uZS8tTkG8jV3BZi83QqYXPcN4v8EjK2Aof4YcEeqLt688mV57gF4j6QZWdP9U", $bip44ChildKey->toExtendedKey());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testCreateHeirarchicalPrivateKey(EcAdapterInterface $ecAdapter)
    {
        $key = 'xprv9s21ZrQH143K24zyWeuwtaWrpNjzYRX9VNSFgT6TwC8aBK46j95aWJM7rW9uek4M9BNosaoN8fLFMi3UVMAynimfuf164nXoZpaQJa2FXpU';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network, $ecAdapter);

        $this->assertInstanceOf($this->baseType, $this->key);
        $this->assertSame($this->key->toExtendedPrivateKey($this->network), $key);
        $this->assertSame($this->key->toExtendedKey($this->network), $key);
        $this->assertTrue($this->key->isPrivate());

        $key = 'xpub661MyMwAqRbcEZ5ScgSxFiTbNQaUwtEzrbMrUqW5VXfZ47PFGgPq46fbhkpYCkxZQRDxhFy53Nip1VJCofd7auHCrPCmP72NV4YWu2HB7ir';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network, $ecAdapter);

        $this->assertInstanceOf($this->baseType, $this->key);
        $this->assertSame($this->key->toExtendedPublicKey($this->network), $key);
        $this->assertSame($this->key->toExtendedKey($this->network), $key);
        $this->assertFalse($this->key->isPrivate());
    }

    /**
     * This tests that a network always must have the HD priv/pub bytes
     * @expectedException \Exception
     */
    public function testCreateWithInvalidNetworkHDBytes()
    {
        $network = new Network('ff', 'ff', 'ff');
        $key = 'xpub661MyMwAqRbcEZ5ScgSxFiTbNQaUwtEzrbMrUqW5VXfZ47PFGgPq46fbhkpYCkxZQRDxhFy53Nip1VJCofd7auHCrPCmP72NV4YWu2HB7ir';
        HierarchicalKeyFactory::fromExtended($key, $network);
    }

    /**
     * This tests if the key being decoded has bytes which match the network.
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  HD key magic bytes do not match network magic bytes
     */
    public function testCreateWithInvalidNetwork()
    {
        $network = NetworkFactory::create('ff','ff','ff')
            ->setHDPrivByte('ffffffff')
            ->setHDPubByte('ffffffff');

        $key = 'xpub661MyMwAqRbcEZ5ScgSxFiTbNQaUwtEzrbMrUqW5VXfZ47PFGgPq46fbhkpYCkxZQRDxhFy53Nip1VJCofd7auHCrPCmP72NV4YWu2HB7ir';
        HierarchicalKeyFactory::fromExtended($key, $network);
    }

    /**
     * @expectedException \Exception
     */
    public function testCreateWithInvalidLength()
    {
        $key       = 'KyQZJyRyxqNBc31iWzZjUf1vDMXpbcUzwND6AANq44M3v38smDkA';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);
    }

    /**
     * @dataProvider getEcAdapters
     */
    public function testFromExtended(EcAdapterInterface $ecAdapter)
    {
        $xprv = 'xprv9s21ZrQH143K3QTDL4LXw2F7HEK3wJUD2nW2nRk4stbPy6cq3jPPqjiChkVvvNKmPGJxWUtg6LnF5kejMRNNU3TGtRBeJgk33yuGBxrMPHi';
        $xpub = 'xpub661MyMwAqRbcFtXgS5sYJABqqG9YLmC4Q1Rdap9gSE8NqtwybGhePY2gZ29ESFjqJoCu1Rupje8YtGqsefD265TMg7usUDFdp6W1EGMcet8';

        $this->key = HierarchicalKeyFactory::fromExtended($xprv, $this->network, $ecAdapter);

        $this->assertSame($xprv, $this->key->toExtendedKey($this->network));
        $this->assertSame($xprv, $this->key->toExtendedPrivateKey($this->network));
        $this->assertSame($xpub, $this->key->toExtendedPublicKey($this->network));
        $this->assertInstanceOf($this->baseType, $this->key);
    }

    /**
     * @dataProvider getEcAdapters
     * @expectedException \Exception
     */
    public function testGetExtendedPrivateKeyFailure(EcAdapterInterface $ecAdapter)
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network, $ecAdapter);
        $this->key->toExtendedPrivateKey($this->network);
    }

    /**
     * @dataProvider getEcAdapters
     */
    public function testMasterKeyDepthIsZero(EcAdapterInterface $ecAdapter)
    {
        $key       = 'xprv9s21ZrQH143K24zyWeuwtaWrpNjzYRX9VNSFgT6TwC8aBK46j95aWJM7rW9uek4M9BNosaoN8fLFMi3UVMAynimfuf164nXoZpaQJa2FXpU';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network, $ecAdapter);
        $this->assertSame($this->key->getDepth(), '0');
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testGetDepth(EcAdapterInterface $ecAdapter)
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network, $ecAdapter);
        $this->assertSame($this->key->getDepth(), '2');
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testIsHardened(EcAdapterInterface $ecAdapter)
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network, $ecAdapter);
        $this->assertFalse($this->key->isHardened());

        $key       = 'xprv9uHRZZhk6KAJC1avXpDAp4MDc3sQKNxDiPvvkX8Br5ngLNv1TxvUxt4cV1rGL5hj6KCesnDYUhd7oWgT11eZG7XnxHrnYeSvkzY7d2bhkJ7';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network, $ecAdapter);
        $this->assertTrue($this->key->isHardened());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testGetFingerprint(EcAdapterInterface $ecAdapter)
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network, $ecAdapter);
        $this->assertSame(Bitcoin::getMath()->hexDec('615914f3'), $this->key->getFingerprint());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testGetChildFingerprint(EcAdapterInterface $ecAdapter)
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network, $ecAdapter);
        $this->assertSame(Bitcoin::getMath()->hexDec('a282920f'), $this->key->getChildFingerprint());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testGetPrivateKey(EcAdapterInterface $ecAdapter)
    {
        $key       = 'xprv9uHRZZhk6KAJC1avXpDAp4MDc3sQKNxDiPvvkX8Br5ngLNv1TxvUxt4cV1rGL5hj6KCesnDYUhd7oWgT11eZG7XnxHrnYeSvkzY7d2bhkJ7';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network, $ecAdapter);
        $this->assertSame('edb2e14f9ee77d26dd93b4ecede8d16ed408ce149b6cd80b0715a2d911a0afea', $this->key->getPrivateKey()->getBuffer()->getHex());
    }

    /**
     * @dataProvider getEcAdapters
     * @depends testGetPrivateKey
     * @param EcAdapterInterface $ecAdapter
     * @expectedException \Exception
     */
    public function testGetPrivateKeyFailure(EcAdapterInterface $ecAdapter)
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network, $ecAdapter);
        $this->assertSame('edb2e14f9ee77d26dd93b4ecede8d16ed408ce149b6cd80b0715a2d911a0afea', $this->key->getPrivateKey());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testGetPublicKey(EcAdapterInterface $ecAdapter)
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network, $ecAdapter);
        $this->assertSame('02e399a713db445b33340034ee5f71cd782bd9bc78f6f3352ca640109fe24ca23a', $this->key->getPublicKey()->getBuffer()->getHex());
    }

    /**
     * @dataProvider getEcAdapters
     * @expectedException \Exception
     */
    public function testDeriveFailure(EcAdapterInterface $ecAdapter)
    {
        $k         = 'xpub661MyMwAqRbcFtXgS5sYJABqqG9YLmC4Q1Rdap9gSE8NqtwybGhePY2gZ29ESFjqJoCu1Rupje8YtGqsefD265TMg7usUDFdp6W1EGMcet8';
        $this->key = HierarchicalKeyFactory::fromExtended($k, $this->network, $ecAdapter);
        $this->key->deriveChild("2147483648");
    }

}
