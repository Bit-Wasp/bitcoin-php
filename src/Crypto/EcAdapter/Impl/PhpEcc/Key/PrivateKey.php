<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Key\PrivateKeySerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\Key;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Exceptions\InvalidPrivateKey;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\PrivateKey\WifPrivateKeySerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use Mdanter\Ecc\Crypto\EcDH\EcDH;
use Mdanter\Ecc\Primitives\GeneratorPoint;

class PrivateKey extends Key implements PrivateKeyInterface, \Mdanter\Ecc\Crypto\Key\PrivateKeyInterface
{
    /**
     * @var \GMP
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
     * @var EcAdapter
     */
    private $ecAdapter;

    /**
     * @param EcAdapter $ecAdapter
     * @param \GMP $int
     * @param bool $compressed
     * @throws InvalidPrivateKey
     */
    public function __construct(EcAdapter $ecAdapter, \GMP $int, bool $compressed = false)
    {
        if (false === $ecAdapter->validatePrivateKey(Buffer::int(gmp_strval($int, 10), 32, $ecAdapter->getMath()))) {
            throw new InvalidPrivateKey('Invalid private key - must be less than curve order.');
        }

        if (false === is_bool($compressed)) {
            throw new \InvalidArgumentException('PrivateKey: Compressed argument must be a boolean');
        }
        
        $this->ecAdapter = $ecAdapter;
        $this->secretMultiplier = $int;
        $this->compressed = $compressed;
    }

    /**
     * @return \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    public function getPoint(): GeneratorPoint
    {
        return $this->ecAdapter->getGenerator();
    }

    /**
     * @return \GMP
     */
    public function getSecret(): \GMP
    {
        return $this->secretMultiplier;
    }

    /**
     * @param \Mdanter\Ecc\Crypto\Key\PublicKeyInterface $recipient
     * @return EcDH
     */
    public function createExchange(\Mdanter\Ecc\Crypto\Key\PublicKeyInterface $recipient)
    {
        $ecdh = new EcDH($this->ecAdapter->getMath());
        $ecdh->setSenderKey($this);
        $ecdh->setRecipientKey($recipient);
        return $ecdh;
    }

    /**
     * @param BufferInterface $msg32
     * @param RbgInterface|null $rbg
     * @return SignatureInterface
     */
    public function sign(BufferInterface $msg32, RbgInterface $rbg = null): SignatureInterface
    {
        return $this->ecAdapter->sign($msg32, $this, $rbg);
    }

    /**
     * @param \GMP $tweak
     * @return KeyInterface
     */
    public function tweakAdd(\GMP $tweak): KeyInterface
    {
        $adapter = $this->ecAdapter;
        $modMath = $adapter->getMath()->getModularArithmetic($adapter->getGenerator()->getOrder());
        return $adapter->getPrivateKey($modMath->add($tweak, $this->getSecret()), $this->compressed);
    }

    /**
     * @param \GMP $tweak
     * @return KeyInterface
     */
    public function tweakMul(\GMP $tweak): KeyInterface
    {
        $adapter = $this->ecAdapter;
        $modMath = $adapter->getMath()->getModularArithmetic($adapter->getGenerator()->getOrder());
        return $adapter->getPrivateKey($modMath->mul($tweak, $this->getSecret()), $this->compressed);
    }

    /**
     * {@inheritDoc}
     */
    public function isCompressed(): bool
    {
        return $this->compressed;
    }

    /**
     * Return the public key
     *
     * @return PublicKey
     */
    public function getPublicKey(): PublicKey
    {
        if (null === $this->publicKey) {
            $adapter = $this->ecAdapter;
            $this->publicKey = $adapter->getPublicKey($adapter->getGenerator()->mul($this->secretMultiplier), $this->compressed);
        }

        return $this->publicKey;
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function toWif(NetworkInterface $network = null): string
    {
        $network = $network ?: Bitcoin::getNetwork();
        $serializer = new WifPrivateKeySerializer(
            $this->ecAdapter,
            new PrivateKeySerializer($this->ecAdapter)
        );

        return $serializer->serialize($network, $this);
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer(): BufferInterface
    {
        return (new PrivateKeySerializer($this->ecAdapter))->serialize($this);
    }
}
