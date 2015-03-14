<?php

namespace Afk11\Bitcoin\Key;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Exceptions\InvalidPrivateKey;
use Afk11\Bitcoin\Network\NetworkInterface;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\Serializer\Key\PrivateKey\HexPrivateKeySerializer;
use Afk11\Bitcoin\Serializer\Key\PrivateKey\WifPrivateKeySerializer;
use Mdanter\Ecc\GeneratorPoint;

class PrivateKey extends Key implements PrivateKeyInterface
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
     * @var bool
     */
    private $compressed;

    /**
     * @var PublicKey
     */
    protected $publicKey;

    /**
     * @param Math $math
     * @param GeneratorPoint $generator
     * @param $int
     * @param bool $compressed
     * @throws InvalidPrivateKey
     */
    public function __construct(
        Math $math,
        GeneratorPoint $generator,
        $int,
        $compressed = false
    ) {

        if (!self::isValidKey($int)) {
            throw new InvalidPrivateKey('Invalid private key - must be less than curve order.');
        }

        $this->math = $math;
        $this->generator = $generator;
        $this->secretMultiplier = $int;
        $this->compressed = $compressed;
    }

    /**
     * Check if the $hex string is a valid key, ie, less than the order of the curve.
     *
     * @param $int
     * @return bool
     */
    public static function isValidKey($int)
    {
        $math = Bitcoin::getMath();
        $generator = Bitcoin::getGenerator();

        // Less than the order of the curve, and not zero
        $withinRange = $math->cmp($int, $generator->getOrder()) < 0;
        $notZero = !($math->cmp($int, '0') === 0);

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
            $this->publicKey = new PublicKey($this->math, $this->generator, $point, $this->compressed);
        }

        return $this->publicKey;
    }

    /**
     * Return the hash of the associated public key
     *
     * @return string
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
     * @return \Afk11\Bitcoin\Buffer
     */
    public function getBuffer()
    {
        $hexSerializer = new HexPrivateKeySerializer($this->math, $this->generator);
        $hex = $hexSerializer->serialize($this);
        return $hex;
    }
}
