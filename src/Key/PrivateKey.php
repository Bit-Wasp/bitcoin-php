<?php

namespace Afk11\Bitcoin\Key;

use \Afk11\Bitcoin\Bitcoin;
use \Afk11\Bitcoin\Exceptions\InvalidPrivateKey;
use Afk11\Bitcoin\NetworkInterface;
use \Afk11\Bitcoin\SerializableInterface;
use \Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\Serializer\Key\PrivateKey\HexPrivateKeySerializer;
use Afk11\Bitcoin\Serializer\Key\PrivateKey\WifPrivateKeySerializer;
use Mdanter\Ecc\GeneratorPoint;

class PrivateKey implements PrivateKeyInterface, SerializableInterface
{
    /**
     * @var int|string
     */
    protected $secretMultiplier;

    /**
     * @var
     */
    private $math;

    /**
     * @var GeneratorPoint
     */
    private $generator;

    /**
     * @var PublicKey
     */
    protected $publicKey;

    /**
     * @param Math $math
     * @param GeneratorPoint $generator
     * @param $hex
     * @param bool $compressed
     * @throws InvalidPrivateKey
     */
    public function __construct(
        Math $math,
        GeneratorPoint $generator,
        $int,
        $compressed = false
    ) {

        if (! self::isValidKey($int)) {
            throw new InvalidPrivateKey('Invalid private key - must be less than curve order.');
        }

        $this->math = $math;
        $this->generator = $generator;
        $this->secretMultiplier = $int;
        $this->compressed = $compressed;

        return $this;
    }

    /**
     * Check if the $hex string is a valid key, ie, less than the order of the curve.
     *
     * @param $hex
     * @return bool
     */
    public static function isValidKey($int)
    {
        $math = Bitcoin::getMath();
        $generator = Bitcoin::getGenerator();

        // Less than the order of the curve, and not zero
        $withinRange = $math->cmp($int, $generator->getOrder()) < 0;
        $notZero = ! ($math->cmp($int, '0') === 0);

        return $withinRange && $notZero;
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
        return $this->compressed;
    }

    /**
     * Return the public key
     *
     * @return PublicKey
     */
    public function getPublicKey()
    {
        if ($this->publicKey == null) {
            $point = $this->generator->mul($this->getSecretMultiplier());
            $this->publicKey  = new PublicKey($this->math, $point, $this->compressed);
        }

        return $this->publicKey;
    }

    /**
     * Return the hash of the associated public key
     *
     * @return mixed
     */
    public function getPubKeyHash()
    {
        return $this->getPublicKey()->getPubKeyHash();
    }

    /**
     * Set that this key should be compressed
     *
     * @param $setting
     * @return $this
     * @throws \Exception
     */
    public function setCompressed($setting)
    {
        $this->compressed = $setting;
        $this->getPublicKey()->setCompressed($setting);

        return $this;
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function toWif(NetworkInterface $network = null)
    {
        $network ?: Bitcoin::getNetwork();

        $wifSerializer = new WifPrivateKeySerializer($this->math, new HexPrivateKeySerializer($this->math, $this->generator));
        $wif = $wifSerializer->serialize($network, $this);
        return $wif;
    }

    /**
     * @return string
     */
    public function toHex()
    {
        $hexSerializer = new HexPrivateKeySerializer($this->math, $this->generator);

        $hex = $hexSerializer->serialize($this);
        return $hex;
    }

    /**
     * Serialize to desired type: hex, decimal, or binary
     *
     * @param null $type
     * @return int|mixed|string
     */
    public function serialize($type = null)
    {
        if ($type == 'hex') {
            return $this->toHex();
        } elseif ($type == 'int') {
            return $this->getSecretMultiplier();
        } else {
            return pack("H*", $this->toHex());
        }
    }

    /**
     * Return the length of the private key. 32 for binary, 64 for hex.
     *
     * @param null $type
     * @return int
     */
    public function getSize($type = null)
    {
        if ($type == 'hex') {
            return strlen($this->toHex());
        } else {
            return 32;
        }
    }

    /**
     * Return hex string representation of private key
     * @return string
     */
    public function __toString()
    {
        return $this->toHex();
    }
}
