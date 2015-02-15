<?php

namespace Afk11\Bitcoin\Key;

use \Afk11\Bitcoin\Bitcoin;
use \Afk11\Bitcoin\Exceptions\Base58ChecksumFailure;
use \Afk11\Bitcoin\Exceptions\InvalidPrivateKey;
use \Afk11\Bitcoin\NetworkInterface;
use \Afk11\Bitcoin\SerializableInterface;
use \Afk11\Bitcoin\Signature\Signature;
use \Afk11\Bitcoin\Signature\K\KInterface;
use \Afk11\Bitcoin\Math\Math;
use \Afk11\Bitcoin\Buffer;
use \Afk11\Bitcoin\Base58;
use \Afk11\Bitcoin\Crypto\Random\Random;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\GeneratorPoint;

class PrivateKey implements KeyInterface, PrivateKeyInterface, SerializableInterface
{
    /**
     * @var int
     */
    protected $secretMultiplier;

    /**
     * @var \Mdanter\Ecc\CurveFp
     */
    protected $curve;

    /**
     * @var PublicKey
     */
    protected $publicKey = null;
    /**
     * @var
     */
    private $math;

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
        $hex,
        $compressed = false
    ) {
        if ($hex instanceof Buffer) {
            $hex = $hex->serialize('hex');
        }

        if (! self::isValidKey($hex)) {
            throw new InvalidPrivateKey('Invalid private key - must be less than curve order.');
        }

        $this->math = $math;
        $this->generator = $generator;
        $this->secretMultiplier = $this->math->hexDec($hex);
        $this->compressed       = $compressed;

        return $this;
    }

    /**
     * Instantiate the class when given a WIF private key.
     *
     * @param string $wif
     * @return PrivateKey
     * @throws Base58ChecksumFailure
     */
    public static function fromWIF($wif)
    {
        try {
            $data = Base58::decodeCheck($wif);
            $hex  = substr($data, 2, 64);
            $key  = new PrivateKey(Bitcoin::getMath(), Bitcoin::getGenerator(), $hex, (strlen($data) == 68));

        } catch (Base58ChecksumFailure $e) {
            throw new Base58ChecksumFailure('Failed to decode WIF - was it copied correctly?');
        }

        return $key;
    }

    /**
     * Generate a new private key from entropy
     *
     * @param bool $compressed
     * @return PrivateKey
     */
    public static function generateNew($compressed = false)
    {
        $keyBuffer = self::generateKey();
        $private   = new PrivateKey(Bitcoin::getMath(), Bitcoin::getGenerator(), $keyBuffer->serialize('hex'), $compressed);
        return $private;
    }

    /**
     * Check if the $hex string is a valid key, ie, less than the order of the curve.
     *
     * @param $hex
     * @return bool
     */
    public static function isValidKey($hex)
    {
        $math        = Bitcoin::getMath();
        $generator   = Bitcoin::getGenerator();

        // Less than the order of the curve
        $withinRange = $math->cmp($math->hexDec($hex), $generator->getOrder()) < 0;

        // Not zero
        $notZero     = ! ($math->cmp($math->hexDec($hex), 0) == 0);

        return $withinRange and $notZero;
    }
    /**
     * Generate a buffer containing a valid key
     *
     * @return Buffer
     * @throws \Afk11\Bitcoin\Exceptions\RandomBytesFailure
     */
    public static function generateKey()
    {
        $random = new Random();
        do {
            $buffer = $random->bytes(32);
        } while (! self::isValidKey($buffer->serialize('hex')));

        return $buffer;
    }

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
     * @inheritdoc
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
            $point = Bitcoin::getGenerator()->mul($this->serialize('int'));
            $this->publicKey  = new PublicKey($point, $this->compressed);
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
        $this->getPublicKey();
        $this->publicKey->setCompressed($setting);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCurve()
    {
        return Bitcoin::getGenerator()->getCurve();
    }

    /**
     * Return the hex representation of the key
     * @return string
     */
    private function getHex()
    {
        $hex = Bitcoin::getMath()->decHex($this->secretMultiplier);
        return str_pad($hex, 64, '0', STR_PAD_LEFT);
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
            return $this->getHex();
        } elseif ($type == 'int') {
            return $this->secretMultiplier;
        } else {
            return pack("H*", $this->getHex());
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
            return strlen($this->getHex());
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
        return $this->getHex();
    }

    /**
     * When given a network, return a WIF
     *
     * @param NetworkInterface $network
     * @return string
     */
    public function getWif(NetworkInterface $network)
    {
        $hex = sprintf(
            "%s%s%s",
            $network->getPrivByte(),
            $this->getHex(),
            ($this->isCompressed() ? '01' : '')
        );

        return Base58::encodeCheck($hex);
    }
}
