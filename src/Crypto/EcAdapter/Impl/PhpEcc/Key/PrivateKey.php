<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Key\PrivateKeySerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\Key;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Exceptions\InvalidPrivateKey;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\PrivateKey\WifPrivateKeySerializer;
use BitWasp\Buffertools\Buffer;

class PrivateKey extends Key implements PrivateKeyInterface
{
    /**
     * @var int|string
     */
    private $secretMultiplier;

    /**
     * @var bool
     */
    private $compressed;

    /**
     * @var PublicKey
     */
    private $publicKey;

    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @param EcAdapter $ecAdapter
     * @param $int
     * @param bool $compressed
     * @throws InvalidPrivateKey
     */
    public function __construct(EcAdapter $ecAdapter, $int, $compressed = false)
    {
        if (false === $ecAdapter->validatePrivateKey(Buffer::int($int, 32))) {
            throw new InvalidPrivateKey('Invalid private key - must be less than curve order.');
        }
        $this->ecAdapter = $ecAdapter;
        $this->secretMultiplier = $int;
        $this->compressed = $compressed;
    }

    /**
     * @return int|string
     */
    public function getSecretMultiplier()
    {
        return $this->secretMultiplier;
    }

    /**
     * @param Buffer $msg32
     * @param RbgInterface|null $rbg
     * @return \BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface
     */
    public function sign(Buffer $msg32, RbgInterface $rbg = null)
    {
        return $this->ecAdapter->sign($msg32, $this, $rbg);
    }

    /**
     * @param int $tweak
     * @return PrivateKeyInterface
     */
    public function tweakAdd($tweak)
    {
        $adapter = $this->ecAdapter;
        $math = $adapter->getMath();
        return $adapter->getPrivateKey(
            $math->mod(
                $math->add(
                    $tweak,
                    $this->getSecretMultiplier()
                ),
                $this->ecAdapter->getGenerator()->getOrder()
            ),
            $this->compressed
        );
    }

    /**
     * @param int $tweak
     * @return PrivateKeyInterface
     */
    public function tweakMul($tweak)
    {
        $adapter = $this->ecAdapter;
        $math = $adapter->getMath();
        return $adapter->getPrivateKey(
            $math->mod(
                $math->mul(
                    $tweak,
                    $this->getSecretMultiplier()
                ),
                $this->ecAdapter->getGenerator()->getOrder()
            ),
            $this->compressed
        );
    }

    /**
     * Always returns true when private key.
     *
     * @return bool
     */
    public function isPrivate()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isCompressed()
    {
        return $this->compressed === true;
    }

    /**
     * Return the public key
     *
     * @return PublicKey
     */
    public function getPublicKey()
    {
        if ($this->publicKey == null) {
            $adapter = $this->ecAdapter;
            $point = $adapter->getGenerator()->mul($this->secretMultiplier);
            $this->publicKey = $adapter->getPublicKey($point, $this->compressed);
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
     * Set that this key should be compressed
     *
     * @param boolean $setting
     * @return $this
     * @throws \Exception
     */
    public function setCompressed($setting)
    {
        $this->compressed = $setting;
        return $this;
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function toWif(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        $serializer = new WifPrivateKeySerializer(
            $this->ecAdapter->getMath(),
            new PrivateKeySerializer($this->ecAdapter)
        );

        return $serializer->serialize($network, $this);
    }

    /**
     * @return \BitWasp\Buffertools\Buffer
     */
    public function getBuffer()
    {
        return EcSerializer::getSerializer($this->ecAdapter, \BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PrivateKeySerializerInterface::class);
    }
}
