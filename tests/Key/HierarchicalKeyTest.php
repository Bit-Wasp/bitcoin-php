<?php

namespace Afk11\Bitcoin\Tests\Key;

use Afk11\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use Afk11\Bitcoin\Key\HierarchicalKeyFactory;
use Afk11\Bitcoin\Network;
use Afk11\Bitcoin\Key\HierarchicalKey;

class HierarchicalKeyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HierarchicalKey
     */
    protected $key;

    /**
     * @var Network
     */
    protected $network;

    protected $baseType = 'Afk11\Bitcoin\Key\HierarchicalKey';

    public function setUp()
    {
        $this->key = null;
        $this->network = new Network('00', '05', '80', false);
        $this->network->setHDPubByte('0488b21e')
            ->setHDPrivByte('0488ade4');
    }

    public function testCreateHeirarchicalPrivateKey()
    {
        $key = 'xprv9s21ZrQH143K24zyWeuwtaWrpNjzYRX9VNSFgT6TwC8aBK46j95aWJM7rW9uek4M9BNosaoN8fLFMi3UVMAynimfuf164nXoZpaQJa2FXpU';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);

        $this->assertInstanceOf($this->baseType, $this->key);
        $this->assertSame($this->key->toExtendedPrivateKey($this->network), $key);
        $this->assertTrue($this->key->isCompressed());
        $this->assertTrue($this->key->isPrivate());
    }

    public function testCreateHeirarchicalPublicKey()
    {
        $key = 'xpub661MyMwAqRbcEZ5ScgSxFiTbNQaUwtEzrbMrUqW5VXfZ47PFGgPq46fbhkpYCkxZQRDxhFy53Nip1VJCofd7auHCrPCmP72NV4YWu2HB7ir';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);

        $this->assertInstanceOf($this->baseType, $this->key);
        $this->assertSame($this->key->toExtendedPublicKey($this->network), $key);
        $this->assertTrue($this->key->isCompressed());
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
        $this->key = HierarchicalKeyFactory::fromExtended($key);
    }

    public function testFromExtended()
    {
        $xprv = 'xprv9s21ZrQH143K24zyWeuwtaWrpNjzYRX9VNSFgT6TwC8aBK46j95aWJM7rW9uek4M9BNosaoN8fLFMi3UVMAynimfuf164nXoZpaQJa2FXpU';
        $xpub = 'xpub661MyMwAqRbcEZ5ScgSxFiTbNQaUwtEzrbMrUqW5VXfZ47PFGgPq46fbhkpYCkxZQRDxhFy53Nip1VJCofd7auHCrPCmP72NV4YWu2HB7ir';

        // Import private
        $this->key = HierarchicalKeyFactory::fromExtended($xprv, $this->network);

        $this->assertSame($xprv, $this->key->toExtendedPrivateKey($this->network));
        $this->assertInstanceOf($this->baseType, $this->key);
        //$this->assertSame($xpub, $this->key->toExtendedPublicKey($this->network));
    }

    public function testGetDepthRoot()
    {
        $key       = 'xprv9s21ZrQH143K24zyWeuwtaWrpNjzYRX9VNSFgT6TwC8aBK46j95aWJM7rW9uek4M9BNosaoN8fLFMi3UVMAynimfuf164nXoZpaQJa2FXpU';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);
        $this->assertSame($this->key->getDepth(), '0');
    }

    public function testGetDepth()
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);
        $this->assertSame($this->key->getDepth(), '2');
    }

    public function testGetBytes()
    {
        // We have gone agnostic
        //$key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        //print_r($this->network);
        //$this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);
        //$this->assertSame($this->network->getHDPubByte(), $this->key->getBytes());
    }

    public function testIsHardenedFalse()
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);
        $this->assertFalse($this->key->isHardened());
    }

    public function testIsHardenedTrue()
    {
        $key       = 'xprv9uHRZZhk6KAJC1avXpDAp4MDc3sQKNxDiPvvkX8Br5ngLNv1TxvUxt4cV1rGL5hj6KCesnDYUhd7oWgT11eZG7XnxHrnYeSvkzY7d2bhkJ7';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);
        $this->assertTrue($this->key->isHardened());
    }

    public function testGetFingerprint()
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);
        $this->assertSame('615914f3', $this->key->getFingerprint());
    }

    public function testGetChildFingerprint()
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);
        $this->assertSame('a282920f', $this->key->getChildFingerprint());
    }

    public function testGetPrivateKey()
    {
        $key       = 'xprv9uHRZZhk6KAJC1avXpDAp4MDc3sQKNxDiPvvkX8Br5ngLNv1TxvUxt4cV1rGL5hj6KCesnDYUhd7oWgT11eZG7XnxHrnYeSvkzY7d2bhkJ7';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);
        $this->assertSame('edb2e14f9ee77d26dd93b4ecede8d16ed408ce149b6cd80b0715a2d911a0afea', $this->key->getPrivateKey()->serialize('hex'));
    }

    /**
     * @depends testGetPrivateKey
     * @expectedException \Exception
     */
    public function testGetPrivateKeyFailure()
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);
        $this->assertSame('edb2e14f9ee77d26dd93b4ecede8d16ed408ce149b6cd80b0715a2d911a0afea', $this->key->getPrivateKey()->serialize('hex'));
        ;
    }

    public function testGetPublicKey()
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);
        $this->assertSame('02e399a713db445b33340034ee5f71cd782bd9bc78f6f3352ca640109fe24ca23a', $this->key->getPublicKey()->serialize('hex'));
    }

    public function testGetExtendedPrivateKey()
    {
        $key       = 'xprv9s21ZrQH143K2xxRcbnPZiHKhAoKbCWH8Wh6QeMdfKbaXR9ycFA8W3zXT8jKm1Rycq147tqdYhgXUxYqBie2V2jPt7y9nJqdKR5PpkQic9g';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);
        $this->assertSame($key, $this->key->toExtendedPrivateKey($this->network));
    }

    /**
     * @depends testGetExtendedPrivateKey
     * @expectedException \Exception
     */
    public function testGetExtendedPrivateKeyFailure()
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);
        $this->assertSame('edb2e14f9ee77d26dd93b4ecede8d16ed408ce149b6cd80b0715a2d911a0afea', $this->key->toExtendedPrivateKey($this->network));
    }

    public function testGetExtendedPublicKey()
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);
        $this->assertSame($key, $this->key->toExtendedPublicKey($this->network));
    }

    public function testGetGenerator()
    {
        $key       = 'xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB';
        $this->key = HierarchicalKeyFactory::fromExtended($key, $this->network);
        $this->assertInstanceOf('Mdanter\Ecc\GeneratorPoint', $this->key->getGenerator());
    }

    public function testGenerateNew()
    {
        $this->key = HierarchicalKeyFactory::generateMasterKey($this->network);
        $this->assertInstanceOf($this->baseType, $this->key);
    }

    public function testFromEntropy()
    {
        $this->key = HierarchicalKeyFactory::fromEntropy('4141414141414141414141414141414141414141414141414141414141414141');
        $this->assertInstanceOf($this->baseType, $this->key);
    }

    public function testGetPubKeyHash()
    {
        $this->key = HierarchicalKeyFactory::fromExtended('xprv9s21ZrQH143K4aS3ykpkKQYP9kKvtkrtmBtWfibAFDqiMYV3YgHmXiTKkqqCqKRfKhfomGUHpSeiPqdD5gx9MXb6UhQGAhynYwDqkP3PY7n', $this->network);
        $this->assertSame('9c35c15b7e5a1776c5d9849ddca196a14960e311', $this->key->getPubKeyHash());
    }

    public function testDecodePath()
    {
        $this->key = HierarchicalKeyFactory::fromExtended('xpub6AV8iVdKGa79ExyueSBjnCNKkmwLQsTvaN2N8iWCT5PNX6Xrh3gPgz3gVrxtLiYyCdC9FjwsuTTXmJiuWkxpLoqo8gj7rPWdkDsUCWfQHJB', $this->network);

        $this->assertSame('m', $this->key->decodePath('m'));
        $this->assertSame('m/1', $this->key->decodePath('m/1'));
        $this->assertSame('m/1/2', $this->key->decodePath('m/1/2'));
        $this->assertSame('m/1/2147483648/3', $this->key->decodePath('m/1/0h/3'));
    }

    /**
     *
     */
    public function testDeriveChild()
    {
        $k         = 'xprv9s21ZrQH143K3QTDL4LXw2F7HEK3wJUD2nW2nRk4stbPy6cq3jPPqjiChkVvvNKmPGJxWUtg6LnF5kejMRNNU3TGtRBeJgk33yuGBxrMPHi';
        $this->key = HierarchicalKeyFactory::fromExtended($k, $this->network);

        $x = $this->key
            ->deriveChild('2147483648', $this->network)
            ->toExtendedPrivateKey($this->network);

        $this->assertSame(
            $x,
            'xprv9uHRZZhk6KAJC1avXpDAp4MDc3sQKNxDiPvvkX8Br5ngLNv1TxvUxt4cV1rGL5hj6KCesnDYUhd7oWgT11eZG7XnxHrnYeSvkzY7d2bhkJ7'
        );
    }

    public function testDeriveChildPublic()
    {
        $k         = 'xpub661MyMwAqRbcFtXgS5sYJABqqG9YLmC4Q1Rdap9gSE8NqtwybGhePY2gZ29ESFjqJoCu1Rupje8YtGqsefD265TMg7usUDFdp6W1EGMcet8';
        $this->key = HierarchicalKeyFactory::fromExtended($k, $this->network);

        $key       = $this->key->deriveChild('0');
        $this->assertInstanceOf($this->baseType, $key);
    }

    /**
     * @expectedException \Exception
     */
    public function testDeriveChildFailure()
    {
        $k         = 'xpub661MyMwAqRbcFtXgS5sYJABqqG9YLmC4Q1Rdap9gSE8NqtwybGhePY2gZ29ESFjqJoCu1Rupje8YtGqsefD265TMg7usUDFdp6W1EGMcet8';
        $this->key = HierarchicalKeyFactory::fromExtended($k, $this->network);

        $this->key->deriveChild("2147483648")->toExtendedPrivateKey($this->network);
    }
}
