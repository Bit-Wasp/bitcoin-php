<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 24/11/14
 * Time: 00:00
 */

namespace Bitcoin;


class PrivateKeyTest extends \PHPUnit_Framework_TestCase {
    protected $privateKey;

    public function setUp()
    {
        $this->privateKey = null;
    }

    public function testIsValidKey()
    {
        // Keys must be < the order of the curve
        // Order of secp256k1 - 1

        $key1 = 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364140';
        $this->assertTrue(PrivateKey::isValidKey($key1));

        $key2 = '4141414141414141414141414141414141414141414141414141414141414141';
        $this->assertTrue(PrivateKey::isValidKey($key2));

        $key3 = '8000000000000000000000000000000000000000000000000000000000000000';
        $this->assertTrue(PrivateKey::isValidKey($key3));

        $key4 = '0000000000000000000000000000000000000000000000000000000000000000';
        $this->assertTrue(PrivateKey::isValidKey($key4));
    }

    /**
     * @depends testIsValidKey
     */
    public function testIsValidKeyFailure()
    {
        // Order of secp256k1
        $order = 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141';
        $this->assertFalse(PrivateKey::isValidKey($order));
    }

    public function testCreatePrivateKey()
    {
        $this->privateKey = new PrivateKey('4141414141414141414141414141414141414141414141414141414141414141');
        $this->assertInstanceOf('Bitcoin\PrivateKey', $this->privateKey);
        $this->assertSame($this->privateKey->serialize('hex'), '4141414141414141414141414141414141414141414141414141414141414141');
        $this->assertFalse($this->privateKey->isCompressed());
    }

    /**
     * @expectedException \Exception
     */
    public function testCreatePrivateKeyFailure()
    {
        $this->privateKey = new PrivateKey('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141');
    }

    public function testGenerateNewUncompressed()
    {
        $this->privateKey = PrivateKey::generateNew(false);
        $this->assertInstanceOf('Bitcoin\PrivateKey', $this->privateKey);
        $this->assertFalse($this->privateKey->isCompressed());
    }


    public function testGenerateNewCompressed()
    {
        $this->privateKey = PrivateKey::generateNew(true);
        $this->assertInstanceOf('Bitcoin\PrivateKey', $this->privateKey);
        $this->assertTrue($this->privateKey->isCompressed());
    }
}
 