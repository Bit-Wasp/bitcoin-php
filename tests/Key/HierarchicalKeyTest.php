<?php

namespace BitWasp\Bitcoin\Tests\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Key\HierarchicalKey;
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
        $this->network = new Network('00', '05', '80', false);
        $this->network->setHDPubByte('0488b21e')
            ->setHDPrivByte('0488ade4');
    }

    private function compareToPrivVectors(HierarchicalKey $key, $vectors)
    {
        $this->assertSame($vectors->secret_wif, $key->getPrivateKey()->toWif($this->network));
        $this->assertSame($vectors->address, $key->getPublicKey()->getAddress()->getAddress($this->network));
        $this->assertSame($vectors->xprv_b58, $key->toExtendedPrivateKey($this->network), 'correct xprv');
        $this->assertSame($vectors->xpub_b58, $key->toExtendedPublicKey($this->network), 'correct xpub');
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
     * @param EcAdapterInterface
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
     * @expectedException \Exception
     */
    public function testCreateWithInvalidNetwork()
    {
        // No longer required, network not required to create an instance
        $network   = new Network('00', '05', '80', false);
        $key       = 'xpub661MyMwAqRbcEZ5ScgSxFiTbNQaUwtEzrbMrUqW5VXfZ47PFGgPq46fbhkpYCkxZQRDxhFy53Nip1VJCofd7auHCrPCmP72NV4YWu2HB7ir';
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
        $this->assertSame('edb2e14f9ee77d26dd93b4ecede8d16ed408ce149b6cd80b0715a2d911a0afea', $this->key->getPrivateKey()->getBuffer()->serialize('hex'));
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
        $this->assertSame('02e399a713db445b33340034ee5f71cd782bd9bc78f6f3352ca640109fe24ca23a', $this->key->getPublicKey()->getBuffer()->serialize('hex'));
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
