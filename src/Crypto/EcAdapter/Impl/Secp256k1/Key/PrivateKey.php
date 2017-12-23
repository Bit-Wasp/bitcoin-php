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
     * @var \GMP
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
     * @param \GMP $secret
     * @param bool|false $compressed
     * @throws \Exception
     */
    public function __construct(EcAdapter $adapter, \GMP $secret, $compressed = false)
    {
        $buffer = Buffer::int(gmp_strval($secret, 10), 32, $adapter->getMath());
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
    public function getSecret()
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
     * @param \GMP $tweak
     * @return PrivateKey
     */
    public function tweakAdd(\GMP $tweak)
    {
        $adapter = $this->ecAdapter;
        $math = $adapter->getMath();
        $context = $adapter->getContext();
        $privateKey = $this->getBinary(); // mod by reference
        $tweak = Buffer::int($math->toString($tweak), 32, $math)->getBinary();
        $ret = \secp256k1_ec_privkey_tweak_add(
            $context,
            $privateKey,
            $tweak
        );

        if ($ret !== 1) {
            throw new \RuntimeException('Secp256k1 privkey tweak add: failed');
        }

        $secret = new Buffer($privateKey);
        return $adapter->getPrivateKey($secret->getGmp(), $this->compressed);
    }

    /**
     * @param \GMP $tweak
     * @return PrivateKey
     */
    public function tweakMul(\GMP $tweak)
    {
        $privateKey = $this->getBinary();
        $math = $this->ecAdapter->getMath();
        $tweak = Buffer::int($math->toString($tweak), 32, $math)->getBinary();
        $ret = \secp256k1_ec_privkey_tweak_mul(
            $this->ecAdapter->getContext(),
            $privateKey,
            $tweak
        );

        if ($ret !== 1) {
            throw new \RuntimeException('Secp256k1 privkey tweak mul: failed');
        }

        $secret = new Buffer($privateKey);

        return $this->ecAdapter->getPrivateKey($secret->getGmp(), $this->compressed);
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function toWif(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        $wifSerializer = new WifPrivateKeySerializer($this->ecAdapter, new PrivateKeySerializer($this->ecAdapter));
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
