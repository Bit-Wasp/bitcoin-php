<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Serializer\Key\PrivateKeySerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature\Signature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\Key;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\PrivateKey\WifPrivateKeySerializer;
use BitWasp\Buffertools\Buffer;

class PrivateKey extends Key implements PrivateKeyInterface
{
    /**
     * @var int|string
     */
    private $secret;

    /**
     * @var string
     */
    private $secretBin;

    /**
     * @var bool
     */
    private $compressed;

    /**
     * @var PublicKey
     */
    private $publicKey;

    /**
     * @var EcAdapter
     */
    private $ecAdapter;

    /**
     * @param EcAdapter $adapter
     * @param int|string $secret
     * @param bool|false $compressed
     */
    public function __construct(EcAdapter $adapter, $secret, $compressed = false)
    {
        $buffer = Buffer::hex(str_pad($adapter->getMath()->decHex($secret), 64, '0', STR_PAD_LEFT));
        if (!$adapter->validatePrivateKey($buffer)) {
            throw new \Exception('Invalid private key');
        }
        $this->ecAdapter = $adapter;
        $this->secret = $secret;
        $this->secretBin = $buffer->getBinary();
        $this->compressed = $compressed;
    }

    /**
     * @param Buffer $msg32
     * @param RbgInterface|null $rbgInterface
     * @return Signature
     */
    public function sign(Buffer $msg32, RbgInterface $rbgInterface = null)
    {
        return $this->ecAdapter->sign($msg32, $this, $rbgInterface);
    }

    /**
     * @return bool|false
     */
    public function isCompressed()
    {
        return $this->compressed;
    }

    /**
     * @return bool
     */
    public function isPrivate()
    {
        return true;
    }

    /**
     * @return int|string
     */
    public function getSecretMultiplier()
    {
        return $this->secret;
    }

    /**
     * @return string
     */
    public function getSecretBinary()
    {
        return $this->secretBin;
    }

    /**
     * @return PublicKey
     */
    public function getPublicKey()
    {
        if ($this->publicKey == null) {
            $context = $this->ecAdapter->getContext();
            $pubkey_t = '';
            if (1 !== secp256k1_ec_pubkey_create($context, $this->getBinary(), $pubkey_t)) {
                throw new \RuntimeException('Failed to create public key');
            }
            /** @var resource $pubkey_t */
            $this->publicKey = new PublicKey($this->ecAdapter, $pubkey_t, $this->compressed);
        }

        return $this->publicKey;
    }

    /**
     * Return the hash of the associated public key
     *
     * @return Buffer
     */
    public function getPubKeyHash()
    {
        return $this->getPublicKey()->getPubKeyHash();
    }

    /**
     * @param int $tweak
     * @return PrivateKey
     */
    public function tweakAdd($tweak)
    {
        $adapter = $this->ecAdapter;
        $math = $adapter->getMath();
        $context = $adapter->getContext();
        $privKey = $this->getBinary(); // mod by reference
        $tweak = pack("H*", str_pad($math->decHex($tweak), 64, '0', STR_PAD_LEFT));
        $ret = \secp256k1_ec_privkey_tweak_add(
            $context,
            $privKey,
            $tweak
        );

        if ($ret !== 1) {
            throw new \RuntimeException('Secp256k1 privkey tweak add: failed');
        }

        $secret = $math->hexDec(bin2hex($privKey));
        return $adapter->getPrivateKey($secret, $this->compressed);
    }

    /**
     * @param int $tweak
     * @return PrivateKey
     */
    public function tweakMul($tweak)
    {
        $adapter = $this->ecAdapter;
        $math = $adapter->getMath();
        $context = $adapter->getContext();
        $privateKey = $this->getBinary(); // mod by reference
        $tweak = pack("H*", str_pad($math->decHex($tweak), 64, '0', STR_PAD_LEFT));
        $ret = \secp256k1_ec_privkey_tweak_mul(
            $context,
            $privateKey,
            $tweak
        );

        if ($ret !== 1) {
            throw new \RuntimeException('Secp256k1 privkey tweak mul: failed');
        }

        $secret = $math->hexDec(bin2hex($privateKey));
        return $adapter->getPrivateKey($secret, $this->compressed);
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function toWif(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        $wifSerializer = new WifPrivateKeySerializer($this->ecAdapter->getMath(), new PrivateKeySerializer($this->ecAdapter));
        return $wifSerializer->serialize($network, $this);
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new PrivateKeySerializer($this->ecAdapter))->serialize($this);
    }
}
