<?php

namespace Bitcoin;

use \Bitcoin\Util\Buffer;
use \Bitcoin\Util\Random;
use \Bitcoin\Util\Hash;
use \Bitcoin\Util\Base58;
use \Bitcoin\Util\Math;
use \Mdanter\Ecc\EccFactory;

/**
 * Class PrivateKey
 * @package Bitcoin
 */
class PrivateKey implements KeyInterface, PrivateKeyInterface, SerializableInterface
{
    /**
     * @var int
     */
    protected $decimal;

    /**
     * @var \Mdanter\Ecc\CurveFp
     */
    protected $curve;

    /**
     * @var PublicKey
     */
    protected $publicKey;

    /**
     * @param \Mdanter\Ecc\CurveFp $curve
     * @param $hex
     * @param bool $compressed
     */
    public function __construct($hex, $compressed = false, \Mdanter\Ecc\GeneratorPoint $generator = null)
    {

        if (! self::isValidKey($hex)) {
            throw new \Exception('Invalid private key - must be less than curve order.');
        }

        $this->decimal    = Math::hexDec($hex);
        $this->compressed = $compressed;

        if ($generator == null) {
            $generator = EccFactory::getSecgCurves()->generator256k1();
        }

        $point = $generator->mul($this->decimal);
        $this->generator  = $generator;
        $this->publicKey  = new PublicKey($point, $this->compressed);
        return $this;
    }

    /**
     * Generate a new private key from entropy
     *
     * @param bool $compressed
     * @param \Mdanter\Ecc\GeneratorPoint $generator
     * @return PrivateKey
     * @throws \Exception
     */
    public static function generateNew($compressed = false, \Mdanter\Ecc\GeneratorPoint $generator = null)
    {
        if ($generator == null) {
            $generator = EccFactory::getSecgCurves()->generator256k1();
        }

        $keyBuffer = self::generateKey($generator);
        $private   = new PrivateKey($keyBuffer->serialize('hex'), $compressed);
        return $private;
    }

    /**
     * Generate a buffer containing a valid key
     *
     * @param \Mdanter\Ecc\GeneratorPoint $generator
     * @return Buffer
     * @throws \Exception
     */
    public static function generateKey(\Mdanter\Ecc\GeneratorPoint $generator = null)
    {
        $buffer = new Buffer(Random::bytes(32));
        while (! self::isValidKey($buffer->serialize('hex'), $generator)) {
            $buffer = new Buffer(Random::bytes(32));
        }
        return $buffer;
    }

    /**
     * Check if the $hex string is a valid key, ie, less than the order of the curve.
     *
     * @param $hex
     * @param \Mdanter\Ecc\GeneratorPoint $generator
     * @return bool
     */
    public static function isValidKey($hex, \Mdanter\Ecc\GeneratorPoint $generator = null)
    {
        if ($generator == null) {
            $generator = EccFactory::getSecgCurves()->generator256k1();
        }

        // Less than the order of the curve
        $withinRange = Math::cmp(Math::hexDec($hex), $generator->getOrder()) < 0;

        // Not zero
        $notZero     = ! Math::cmp(Math::hexDec($hex), 0) == 0;
        return $withinRange and $notZero;
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
        return $this->publicKey;
    }

    /**
     * Return the hash of the associated public key
     * @return mixed
     */
    public function getPubKeyHash()
    {
        return $this->publicKey->getPubKeyHash();
    }

    /**
     * Set whether compressed keys should be returned
     *
     * @param $setting
     */
    public function setCompressed($setting)
    {
        $this->compressed = $setting;
        $this->publicKey->setCompressed($setting);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCurve()
    {
        return $this->curve;
    }

    /**
     * Return the hex representation of the key
     * @return string
     */
    private function getHex()
    {
        return str_pad(Math::decHex($this->decimal), 64, '0', STR_PAD_LEFT);
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
            return $this->decimal;
        } else {
            return pack("H*", $this->getHex());
        }
    }

    /**
     * Return the length of the private key. 32 for binary, 64 for hex.
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
     * @param bool $compressed
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
