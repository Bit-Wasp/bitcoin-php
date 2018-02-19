<?php

namespace BitWasp\Bitcoin\Tests\Key\Deterministic;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use Mdanter\Ecc\EccFactory;

class HierarchicalKeyTest extends AbstractTestCase
{

    /**
     * @var Network
     */
    protected $network;

    /**
     * Used for testing skipped keys
     * @var int
     */
    private $HK_run_count = 0;

    /**
     *
     */
    public function setUp()
    {
        $this->network = NetworkFactory::bitcoin();
    }

    /**
     * @param HierarchicalKey $key
     * @param $vectors
     */
    private function compareToPrivVectors(\BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey $key, $vectors)
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
        $key = HierarchicalKeyFactory::generateMasterKey($ecAdapter);
        $this->assertInstanceOf(HierarchicalKey::class, $key);
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
            new Buffer('', 32),
            PrivateKeyFactory::create(false)
        );
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testFromEntropy(EcAdapterInterface $ecAdapter)
    {
        $entropy = Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141');
        $key = HierarchicalKeyFactory::fromEntropy($entropy, $ecAdapter);
        $this->assertInstanceOf(HierarchicalKey::class, $key);
    }

    /**
     * @return array
     */
    public function getBip32Vectors()
    {
        $json = json_decode($this->dataFile('bip32testvectors.json'));

        $results = [];
        foreach ($json->test as $testC => $test) {
            $entropy = Buffer::hex($test->master);

            foreach ($this->getEcAdapters() as $adapter) {
                $results[] = [$adapter[0], $entropy, $test->details, $test->derivs];
            }
        }

        return $results;
    }

    /**
     * @dataProvider getBip32Vectors
     * @param EcAdapterInterface $ecAdapter
     * @param BufferInterface $entropy
     * @param $details
     * @param $derivs
     */
    public function testTestVectors(EcAdapterInterface $ecAdapter, BufferInterface $entropy, $details, $derivs)
    {
        $key = HierarchicalKeyFactory::fromEntropy($entropy, $ecAdapter);
        $this->compareToPrivVectors($key, $details);

        foreach ($derivs as $childDeriv) {
            $key = $key->derivePath($childDeriv->path);
            $this->compareToPrivVectors($key, $childDeriv->details);
        }
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws \Exception
     */
    public function testDerivePath(EcAdapterInterface $ecAdapter)
    {
        $network = NetworkFactory::bitcoin();
        $entropy = Buffer::hex("000102030405060708090a0b0c0d0e0f");
        $masterKey = HierarchicalKeyFactory::fromEntropy($entropy, $ecAdapter);
        $this->assertEquals("xprv9s21ZrQH143K3QTDL4LXw2F7HEK3wJUD2nW2nRk4stbPy6cq3jPPqjiChkVvvNKmPGJxWUtg6LnF5kejMRNNU3TGtRBeJgk33yuGBxrMPHi", $masterKey->toExtendedKey($network));

        $firstChildKey = $masterKey->derivePath("0");
        $this->assertEquals("xprv9uHRZZhbkedL37eZEnyrNsQPFZYRAvjy5rt6M1nbEkLSo378x1CQQLo2xxBvREwiK6kqf7GRNvsNEchwibzXaV6i5GcsgyjBeRguXhKsi4R", $firstChildKey->toExtendedKey($network));

        $bip44ChildKey = $masterKey->derivePath("44'/0'/0'/0/0");
        $this->assertEquals("xprvA4A9CuBXhdBtCaLxwrw64Jaran4n1rgzeS5mjH47Ds8V67uZS8tTkG8jV3BZi83QqYXPcN4v8EjK2Aof4YcEeqLt688mV57gF4j6QZWdP9U", $bip44ChildKey->toExtendedKey($network));

        // get the "m/44'/0'/0'/0/0" derivation, in 2 steps
        $bip44ChildKey = $masterKey->derivePath("44'/0'");
        $bip44ChildKey = $bip44ChildKey->derivePath("0'/0/0");
        $this->assertEquals("xprvA4A9CuBXhdBtCaLxwrw64Jaran4n1rgzeS5mjH47Ds8V67uZS8tTkG8jV3BZi83QqYXPcN4v8EjK2Aof4YcEeqLt688mV57gF4j6QZWdP9U", $bip44ChildKey->toExtendedKey($network));

        // get the "m/44'/0'/0'/0/0" derivation, in 2 steps
        $bip44ChildKey = $masterKey->derivePath("44'/0'/0'");
        $bip44ChildKey = $bip44ChildKey->derivePath("0/0");
        $this->assertEquals("xprvA4A9CuBXhdBtCaLxwrw64Jaran4n1rgzeS5mjH47Ds8V67uZS8tTkG8jV3BZi83QqYXPcN4v8EjK2Aof4YcEeqLt688mV57gF4j6QZWdP9U", $bip44ChildKey->toExtendedKey($network));

        // get the "m/44'/0'/0'/0/0" derivation, in 2 steps
        $bip44ChildKey = $masterKey->derivePath("44'/0'/0'/0");
        $bip44ChildKey = $bip44ChildKey->derivePath("0");
        $this->assertEquals("xprvA4A9CuBXhdBtCaLxwrw64Jaran4n1rgzeS5mjH47Ds8V67uZS8tTkG8jV3BZi83QqYXPcN4v8EjK2Aof4YcEeqLt688mV57gF4j6QZWdP9U", $bip44ChildKey->toExtendedKey($network));

        // get the "m/44'/0'/0'/0/0" derivation, in single steps
        $bip44ChildKey = $masterKey->derivePath("44'");
        $bip44ChildKey = $bip44ChildKey->derivePath("0'");
        $bip44ChildKey = $bip44ChildKey->derivePath("0'");
        $bip44ChildKey = $bip44ChildKey->derivePath("0");
        $bip44ChildKey = $bip44ChildKey->derivePath("0");
        $this->assertEquals("xprvA4A9CuBXhdBtCaLxwrw64Jaran4n1rgzeS5mjH47Ds8V67uZS8tTkG8jV3BZi83QqYXPcN4v8EjK2Aof4YcEeqLt688mV57gF4j6QZWdP9U", $bip44ChildKey->toExtendedKey($network));
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testCreateHeirarchicalPrivateKey(EcAdapterInterface $ecAdapter)
    {
        $xPrv = 'xprv9s21ZrQH143K24zyWeuwtaWrpNjzYRX9VNSFgT6TwC8aBK46j95aWJM7rW9uek4M9BNosaoN8fLFMi3UVMAynimfuf164nXoZpaQJa2FXpU';
        $key = HierarchicalKeyFactory::fromExtended($xPrv, $this->network, $ecAdapter);

        $this->assertInstanceOf(HierarchicalKey::class, $key);
        $this->assertSame($key->toExtendedPrivateKey($this->network), $xPrv);
        $this->assertSame($key->toExtendedKey($this->network), $xPrv);
        $this->assertTrue($key->isPrivate());

        $xPub = 'xpub661MyMwAqRbcEZ5ScgSxFiTbNQaUwtEzrbMrUqW5VXfZ47PFGgPq46fbhkpYCkxZQRDxhFy53Nip1VJCofd7auHCrPCmP72NV4YWu2HB7ir';
        $key = HierarchicalKeyFactory::fromExtended($xPub, $this->network, $ecAdapter);

        $this->assertInstanceOf(HierarchicalKey::class, $key);
        $this->assertSame($key->toExtendedPublicKey($this->network), $xPub);
        $this->assertSame($key->toExtendedKey($this->network), $xPub);
        $this->assertFalse($key->isPrivate());
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
        $network = NetworkFactory::create('ff', 'ff', 'ff')
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
        $key = 'KyQZJyRyxqNBc31iWzZjUf1vDMXpbcUzwND6AANq44M3v38smDkA';
        HierarchicalKeyFactory::fromExtended($key, $this->network);
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testFromExtended(EcAdapterInterface $ecAdapter)
    {
        $xprv = 'xprv9s21ZrQH143K3QTDL4LXw2F7HEK3wJUD2nW2nRk4stbPy6cq3jPPqjiChkVvvNKmPGJxWUtg6LnF5kejMRNNU3TGtRBeJgk33yuGBxrMPHi';
        $xpub = 'xpub661MyMwAqRbcFtXgS5sYJABqqG9YLmC4Q1Rdap9gSE8NqtwybGhePY2gZ29ESFjqJoCu1Rupje8YtGqsefD265TMg7usUDFdp6W1EGMcet8';

        $key = HierarchicalKeyFactory::fromExtended($xprv, $this->network, $ecAdapter);

        $this->assertSame($xprv, $key->toExtendedKey($this->network));
        $this->assertSame($xprv, $key->toExtendedPrivateKey($this->network));
        $this->assertSame($xpub, $key->toExtendedPublicKey($this->network));
        $this->assertInstanceOf(HierarchicalKey::class, $key);
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @expectedException \Exception
     */
    public function testGetExtendedPrivateKeyFailure(EcAdapterInterface $ecAdapter)
    {
        $xPub = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $key = HierarchicalKeyFactory::fromExtended($xPub, $this->network, $ecAdapter);
        $key->toExtendedPrivateKey($this->network);
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testMasterKeyDepthIsZero(EcAdapterInterface $ecAdapter)
    {
        $xPrv = 'xprv9s21ZrQH143K24zyWeuwtaWrpNjzYRX9VNSFgT6TwC8aBK46j95aWJM7rW9uek4M9BNosaoN8fLFMi3UVMAynimfuf164nXoZpaQJa2FXpU';
        $key = HierarchicalKeyFactory::fromExtended($xPrv, $this->network, $ecAdapter);
        $this->assertSame($key->getDepth(), '0');
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testGetDepth(EcAdapterInterface $ecAdapter)
    {
        $xPub = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $key = HierarchicalKeyFactory::fromExtended($xPub, $this->network, $ecAdapter);
        $this->assertSame($key->getDepth(), '2');
    }/**/

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testIsHardened(EcAdapterInterface $ecAdapter)
    {
        $xPub = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $key = HierarchicalKeyFactory::fromExtended($xPub, $this->network, $ecAdapter);
        $this->assertFalse($key->isHardened());

        $xPub = 'xprv9uHRZZhk6KAJC1avXpDAp4MDc3sQKNxDiPvvkX8Br5ngLNv1TxvUxt4cV1rGL5hj6KCesnDYUhd7oWgT11eZG7XnxHrnYeSvkzY7d2bhkJ7';
        $key = HierarchicalKeyFactory::fromExtended($xPub, $this->network, $ecAdapter);
        $this->assertTrue($key->isHardened());
    }/**/

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testGetFingerprint(EcAdapterInterface $ecAdapter)
    {
        $xPub = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $key = HierarchicalKeyFactory::fromExtended($xPub, $this->network, $ecAdapter);
        $this->assertSame($this->safeMath()->hexDec('615914f3'), $key->getFingerprint());
    }/**/

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testGetChildFingerprint(EcAdapterInterface $ecAdapter)
    {
        $xPub = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $key = HierarchicalKeyFactory::fromExtended($xPub, $this->network, $ecAdapter);
        $this->assertSame($this->safeMath()->hexDec('a282920f'), $key->getChildFingerprint());
    }/**/

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testGetPrivateKey(EcAdapterInterface $ecAdapter)
    {
        $xPrv = 'xprv9uHRZZhk6KAJC1avXpDAp4MDc3sQKNxDiPvvkX8Br5ngLNv1TxvUxt4cV1rGL5hj6KCesnDYUhd7oWgT11eZG7XnxHrnYeSvkzY7d2bhkJ7';
        $key = HierarchicalKeyFactory::fromExtended($xPrv, $this->network, $ecAdapter);
        $this->assertSame('edb2e14f9ee77d26dd93b4ecede8d16ed408ce149b6cd80b0715a2d911a0afea', $key->getPrivateKey()->getBuffer()->getHex());
    }/**/

    /**
     * @dataProvider getEcAdapters
     * @depends testGetPrivateKey
     * @param EcAdapterInterface $ecAdapter
     * @expectedException \Exception
     */
    public function testGetPrivateKeyFailure(EcAdapterInterface $ecAdapter)
    {
        $xPub = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $key = HierarchicalKeyFactory::fromExtended($xPub, $this->network, $ecAdapter);
        $this->assertSame('edb2e14f9ee77d26dd93b4ecede8d16ed408ce149b6cd80b0715a2d911a0afea', $key->getPrivateKey());
    }/**/

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testGetPublicKey(EcAdapterInterface $ecAdapter)
    {
        $xPub = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $xPub = HierarchicalKeyFactory::fromExtended($xPub, $this->network, $ecAdapter);
        $this->assertSame('02e399a713db445b33340034ee5f71cd782bd9bc78f6f3352ca640109fe24ca23a', $xPub->getPublicKey()->getBuffer()->getHex());
    }/**/

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @expectedException \Exception
     */
    public function testDeriveFailure(EcAdapterInterface $ecAdapter)
    {
        $k = 'xpub661MyMwAqRbcFtXgS5sYJABqqG9YLmC4Q1Rdap9gSE8NqtwybGhePY2gZ29ESFjqJoCu1Rupje8YtGqsefD265TMg7usUDFdp6W1EGMcet8';
        $key = HierarchicalKeyFactory::fromExtended($k, $this->network, $ecAdapter);
        $key->deriveChild('2147483648');
    }

    public function getInvalidSequences()
    {
        return [
            [-1],
            [pow(2, 32)],
            [pow(2, 64)],
        ];
    }

    /**
     * @dataProvider getInvalidSequences
     * @param int $sequence
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Sequence is outside valid range, must be >= 0 && <= (2^31)-1
     */
    public function testInvalidSequenceGetHmac($sequence)
    {
        $ecAdapter = Bitcoin::getEcAdapter();
        $key = HierarchicalKeyFactory::fromExtended('xprv9s21ZrQH143K3QTDL4LXw2F7HEK3wJUD2nW2nRk4stbPy6cq3jPPqjiChkVvvNKmPGJxWUtg6LnF5kejMRNNU3TGtRBeJgk33yuGBxrMPHi', $this->network, $ecAdapter);
        $key->getHmacSeed($sequence);
    }

    /**
     * @dataProvider getInvalidSequences
     * @param int $sequence
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Sequence is outside valid range, must be >= 0 && <= (2^31)-1
     */
    public function testInvalidSequenceDeriveChild($sequence)
    {
        $ecAdapter = Bitcoin::getEcAdapter();
        $key = HierarchicalKeyFactory::fromExtended('xprv9s21ZrQH143K3QTDL4LXw2F7HEK3wJUD2nW2nRk4stbPy6cq3jPPqjiChkVvvNKmPGJxWUtg6LnF5kejMRNNU3TGtRBeJgk33yuGBxrMPHi', $this->network, $ecAdapter);
        $key->deriveChild($sequence);
    }

    public function testDerivedKeyWithLeadingZeroes()
    {
        $seed = "d13de7bd1e54422d1a3b3b699a27fb460de2849e7e66a005c647e8e4a54075cb";
        $buffer = Buffer::hex($seed);
        $root = HierarchicalKeyFactory::fromEntropy($buffer);

        $this->assertEquals("c23ab32b36ddff49fae350a1bed8ec6b4d9fc252238dd789b7273ba4416054eb", $root->getChainCode()->getHex());
        $this->assertEquals("xpub661MyMwAqRbcGUbHLLJ5n2DzFAt8mmaDxbmbdimh68m8EiXGEQPiJya4BJat5yMzy4e68VSUoLGCu5uvzf8dUoGvwuJsLE6F1cibmWsxFNn", $root->toExtendedPublicKey());
        $this->assertEquals("xprv9s21ZrQH143K3zWpEJm5QtHFh93eNJrNbNqzqLN5XoE9MvC7gs5TmBFaL2PpaXpDc8FBYVe5EChc73ApjSQ5fWsXS7auHy1MmG6hdpywE1q", $root->toExtendedPrivateKey());
        $this->assertEquals("0000081d1e4bad6731c84450c9a3dbb70e8ba30118d3419f2c74077b7996a078", $root->getPrivateKey()->getHex());

        $child = $root->derivePath("m/44'/0'/0'/0/0'");
        $this->assertEquals("ca27553aa89617e982e621637d6478f564b32738f8bbe2e48d0a58a8e0f6da40", $child->getChainCode()->getHex());
        $this->assertEquals("xpub6GcBnm7FfDg5ERWACCvtuotN6Tdoc37r3SZ1asBHvCWzPkqWn3MVKPWKzy6GsfmdMUGanR3D12dH1cp5tJauuubwc4FAJDn67SH2uUjwAT1", $child->toExtendedPublicKey());
        $this->assertEquals("xprvA3cqPFaMpr7n1wRh6BPtYfwdYRoKCaPzgDdQnUmgMrz1WxWNEW3EmbBr9ieh9BJAsRGKFPLvotb4p4Aq79jddUVKPVJt7exVzLHcv777JVf", $child->toExtendedPrivateKey());
    }

    /**
     *
     */
    public function testSkipsInvalidKey()
    {
        $math = new Math();
        $generator = EccFactory::getSecgCurves($math)->generator256k1();


        $k = $math->sub($generator->getOrder(), gmp_init(1));
        $startPub = PublicKeyFactory::fromHex('0379be667ef9dcbbac55a06295ce870b07029bfcdb2dce28d959f2815b16f81798');

        $mock = $this->getMockBuilder('\BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface')
            ->setMethods([
                'getMath',
                'getAdapterName',
                'getGenerator',
                'publicKeyFromBuffer',
                'recoverYfromX',
                'validateSignatureElement',
                'associateSigs',
                'sign',
                'verify',
                'getPrivateKey',
                'halfOrder',
                'checkInt',
                'signCompact',
                'recover',
                'validatePrivateKey'
            ])
            ->getMock();

        $mock->expects($this->any())
            ->method('getMath')
            ->willReturn($math);

        $mock->expects($this->atLeastOnce())
            ->method('validatePrivateKey')
            ->willReturnCallback(
                function () {
                    $return = true;
                    if ($this->HK_run_count == 0) {
                        $return = false;
                    }
                    $this->HK_run_count++;
                    return $return;
                }
            );


        $privMockBuilder = $this->getMockBuilder('BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface')
            ->setMethods([
                'getSecret',
                'getPublicKey',
                'sign',
                'toWif',

                // Key Interface
                'tweakAdd',
                'tweakMul',
                'isCompressed',
                'isPrivate',
                'getPubKeyHash',
                'getAddress',

                // serializable
                'getBuffer',
                'getInt',
                'getHex',
                'getBinary'
            ]);

        $invalidPriv = $privMockBuilder->getMock();
        $invalidPriv->expects($this->any())
            ->method('isCompressed')
            ->willReturn(true);
        $invalidPriv->expects($this->any())
            ->method('isPrivate')
            ->willReturn(true);
        $invalidPriv->expects($this->any())
            ->method('getSecret')
            ->willReturn($generator->getOrder());

        $mockPriv = $privMockBuilder->getMock();
        $mockPriv->expects($this->any())
            ->method('getSecret')
            ->willReturn($k);

        $mockPriv->expects($this->any())
            ->method('getPublicKey')
            ->willReturn($startPub);

        $mockPriv->expects($this->any())
            ->method('isCompressed')
            ->willReturn(true);

        $mockPriv->expects($this->any())
            ->method('isPrivate')
            ->willReturn(true);

        $mockPriv->expects($this->any())
            ->method('tweakAdd')
            ->willReturn($invalidPriv);

        /** @var EcAdapterInterface $mock */
        /** @var PrivateKeyInterface $mockPriv */
        $key = new \BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey($mock, 0, 0, 0, new Buffer('00', 32), $mockPriv);

        $this->assertEquals(0, $this->HK_run_count);
        $expected = 1;
        $child = $key->deriveChild($expected);
        $this->assertNotEquals($expected, $child->getSequence());
        $this->assertEquals(2, $child->getSequence());
        $this->assertEquals(2, $this->HK_run_count);
        $this->assertEquals(gmp_strval($math->add($k, gmp_init(1)), 10), gmp_strval($child->getPrivateKey()->getSecret(), 10));
    }
}
