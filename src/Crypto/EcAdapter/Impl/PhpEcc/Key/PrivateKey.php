<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Key\PrivateKeySerializer;
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
     * @param $int
     * @param bool $compressed
     * @throws InvalidPrivateKey
     */
    public function __construct(EcAdapter $ecAdapter, $int, $compressed = false)
    {
        if (false === $ecAdapter->validatePrivateKey(Buffer::int($int, 32, $ecAdapter->getMath()))) {
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
     * @return int|string
     */
    public function getSecretMultiplier()
    {
        return $this->secretMultiplier;
    }

    /**
     * @param BufferInterface $msg32
     * @param RbgInterface|null $rbg
     * @return \BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface
     */
    public function sign(BufferInterface $msg32, RbgInterface $rbg = null)
    {
        return $this->ecAdapter->sign($msg32, $this, $rbg);
    }

    /**
     * @param int|string $tweak
     * @return PrivateKeyInterface
     */
    public function tweakAdd($tweak)
    {
        $adapter = $this->ecAdapter;
        return $adapter->getPrivateKey(
            $adapter
                ->getMath()
                ->getModularArithmetic(
                    $adapter
                        ->getGenerator()
                        ->getOrder()
                )
                ->add(
                    $tweak,
                    $this->getSecretMultiplier()
                ),
            $this->compressed
        );
    }

    /**
     * @param int|string $tweak
     * @return PrivateKeyInterface
     */
    public function tweakMul($tweak)
    {
        $adapter = $this->ecAdapter;
        return $adapter->getPrivateKey(
            $adapter
            ->getMath()
            ->getModularArithmetic(
                $adapter
                    ->getGenerator()
                    ->getOrder()
            )
            ->mul(
                $tweak,
                $this->getSecretMultiplier()
            ),
            $this->compressed
        );
    }

    /**
     * {@inheritDoc}
     */
    public function isCompressed()
    {
        return $this->compressed;
    }

    /**
     * Return the public key
     *
     * @return PublicKey
     */
    public function getPublicKey()
    {
        if (null === $this->publicKey) {
            $adapter = $this->ecAdapter;
            $this->publicKey = $adapter->getPublicKey(
                $adapter
                    ->getGenerator()
                    ->mul($this->secretMultiplier),
                $this->compressed
            );
        }

        return $this->publicKey;
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
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function getBuffer()
    {
        return (new PrivateKeySerializer($this->ecAdapter))->serialize($this);
    }
}
