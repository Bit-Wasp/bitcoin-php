<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Exceptions\InvalidPrivateKey;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\PrivateKey\HexPrivateKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\PrivateKey\WifPrivateKeySerializer;

class PrivateKey extends Key implements PrivateKeyInterface
{
    /**
     * @var int|string
     */
    protected $secretMultiplier;

    /**
     * @var bool
     */
    protected $compressed;

    /**
     * @var PublicKey
     */
    protected $publicKey;

    /**
     * @var EcAdapterInterface
     */
    protected $ecAdapter;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param $int
     * @param bool $compressed
     * @throws InvalidPrivateKey
     */
    public function __construct(EcAdapterInterface $ecAdapter, $int, $compressed = false)
    {
        $buffer = Buffer::hex($ecAdapter->getMath()->decHex($int));
        if (false === $ecAdapter->validatePrivateKey($buffer)) {
            throw new InvalidPrivateKey('Invalid private key - must be less than curve order.');
        }
        $this->ecAdapter = $ecAdapter;
        $this->secretMultiplier = $int;
        $this->setCompressed($compressed);
    }

    /**
     * @return int
     */
    public function getSecretMultiplier()
    {
        return $this->secretMultiplier;
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
            $this->publicKey = $this->ecAdapter->privateToPublic($this);
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
        $this->getPublicKey();
        $this->publicKey->setCompressed($setting);

        return $this;
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function toWif(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();

        $wifSerializer = new WifPrivateKeySerializer($this->ecAdapter->getMath(), new HexPrivateKeySerializer($this->ecAdapter));
        $wif = $wifSerializer->serialize($network, $this);
        return $wif;
    }

    /**
     * @return \BitWasp\Bitcoin\Buffer
     */
    public function getBuffer()
    {
        $hexSerializer = new HexPrivateKeySerializer($this->ecAdapter);
        return $hexSerializer->serialize($this);
    }
}
