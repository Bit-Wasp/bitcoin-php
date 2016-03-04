<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Serializer\Key\PrivateKeySerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature\Signature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\Key;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Exceptions\InvalidPrivateKey;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\PrivateKey\WifPrivateKeySerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

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
     * @param $secret
     * @param bool|false $compressed
     * @throws \Exception
     */
    public function __construct(EcAdapter $adapter, $secret, $compressed = false)
    {
        $buffer = Buffer::int($secret, 32, $adapter->getMath());
        if (!$adapter->validatePrivateKey($buffer)) {
            throw new InvalidPrivateKey('Invalid private key');
        }

        if (false === is_bool($compressed)) {
            throw new \InvalidArgumentException('PrivateKey: Compressed argument must be a boolean');
        }

        $this->ecAdapter = $adapter;
        $this->secret = $secret;
        $this->secretBin = $buffer->getBinary();
        $this->compressed = $compressed;
    }

    /**
     * @param BufferInterface $msg32
     * @param RbgInterface|null $rbgInterface
     * @return Signature
     */
    public function sign(BufferInterface $msg32, RbgInterface $rbgInterface = null)
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
        if (null === $this->publicKey) {
            $context = $this->ecAdapter->getContext();
            $publicKey_t = '';
            /** @var resource $publicKey_t */
            if (1 !== secp256k1_ec_pubkey_create($context, $publicKey_t, $this->getBinary())) {
                throw new \RuntimeException('Failed to create public key');
            }

            $this->publicKey = new PublicKey($this->ecAdapter, $publicKey_t, $this->compressed);
        }

        return $this->publicKey;
    }

    /**
     * @param int $tweak
     * @var string $tweak
     * @return PrivateKey
     */
    public function tweakAdd($tweak)
    {
        $adapter = $this->ecAdapter;
        $math = $adapter->getMath();
        $context = $adapter->getContext();
        $privKey = $this->getBinary(); // mod by reference
        $tweak = Buffer::int($tweak, 32, $math)->getBinary();
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
        $tweak = Buffer::int($tweak, 32, $math)->getBinary();
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
     * @return BufferInterface
     */
    public function getBuffer()
    {
        return (new PrivateKeySerializer($this->ecAdapter))->serialize($this);
    }
}
