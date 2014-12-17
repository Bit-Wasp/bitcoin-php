<?php

namespace Bitcoin\Key;

use Bitcoin\Bitcoin;
use Bitcoin\Exceptions\InvalidPrivateKey;
use Bitcoin\NetworkInterface;
use Bitcoin\SerializableInterface;
use Bitcoin\Signature\Signature;
use Bitcoin\Signature\K\KInterface;
use Bitcoin\Util\Math;
use Bitcoin\Util\Buffer;
use Bitcoin\Util\Base58;
use Bitcoin\Crypto\Random;
use Mdanter\Ecc\EccFactory;

/**
 * Class PrivateKey
 * @package Bitcoin
 */
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
     * @param $hex
     * @param bool $compressed
     * @param \Mdanter\Ecc\GeneratorPoint $generator
     * @throws \Exception
     */
    public function __construct($hex, $compressed = false, \Mdanter\Ecc\GeneratorPoint $generator = null)
    {
        if ($hex instanceof Buffer) {
            $hex = $hex->serialize('hex');
        }

        if (! self::isValidKey($hex)) {
            throw new InvalidPrivateKey('Invalid private key - must be less than curve order.');
        }

        $this->generator        = $generator ?: EccFactory::getSecgCurves()->generator256k1();
        $this->compressed       = $compressed;
        $this->secretMultiplier = Bitcoin::getMath()->hexDec($hex);

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
        $generator ?: EccFactory::getSecgCurves()->generator256k1();

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
        do {
            $buffer = Random::bytes(32);
        } while (! self::isValidKey($buffer->serialize('hex'), $generator));

        return $buffer;
    }

    /**
     * Check if the $hex string is a valid key, ie, less than the order of the curve.
     *
     * @param $hex
     * @param \Mdanter\Ecc\GeneratorPoint $generator
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

    public function sign(Buffer $messageHash, KInterface $kProvider)
    {
        $randomK = $kProvider->getK();

        $math = Bitcoin::getMath();
        $G  = Bitcoin::getGenerator();
        $n  = $G->getOrder();
        $k  = $randomK->serialize('int');
        $p1 = $G->mul($k);
        $r  = $p1->getX();

        if ($math->cmp($r, 0) == 0) {
            throw new \RuntimeException('Random number r = 0');
        }

        $s  = $math->mod(
            $math->mul(
                $math->inverseMod($k, $n),
                $math->mod(
                    $math->add(
                        $messageHash->serialize('int'),
                        $math->mul($this->serialize('int'), $r)
                    ),
                    $n
                )
            ),
            $n
        );

        if ($math->cmp($s, 0) == 0) {
            throw new \RuntimeException('Signature s = 0');
        }

        return new Signature($r, $s);
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
