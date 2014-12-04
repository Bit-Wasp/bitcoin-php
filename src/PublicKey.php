<?php

namespace Bitcoin;

use Bitcoin\Util\Math;
use Bitcoin\Util\Hash;
use Bitcoin\Util\NumberTheory;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\PointInterface;
use Mdanter\Ecc\GeneratorPoint;

/**
 * Class PublicKey
 * @package Bitcoin
 */
class PublicKey implements KeyInterface, PublicKeyInterface
{

    /**
     * @var PointInterface
     */
    protected $point;

    /**
     * @var bool
     */
    protected $compressed;

    /**
     * @param PointInterface $point
     * @param bool $compressed
     */
    public function __construct(\Mdanter\Ecc\PointInterface $point, $compressed = false)
    {
        $this->point = $point;
        $this->compressed = $compressed;
        return $this;
    }

    /**
     * @return PointInterface
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * @return int|string
     */
    public function getX()
    {
        return $this->getPoint()->getX();
    }

    /**
     * @return int|string
     */
    public function getY()
    {
        return $this->getPoint()->getY();
    }

    /**
     * @return \Mdanter\Ecc\CurveFpInterface
     */
    public function getCurve()
    {
        return $this->getPoint()->getCurve();
    }

    /**
     * @return bool
     */
    public function isCompressed()
    {
        return $this->compressed;
    }

    /**
     * @return mixed|string
     */
    public function getPubKeyHash()
    {
        $publicKey = $this->serialize('hex');

        $hash = Hash::sha256ripe160($publicKey);
        return $hash;
    }

    /**
     * Serialize this according to requested type
     *
     * @return string
     */
    public function serialize($type = null)
    {
        $hex = $this->getPubKeyHex();

        if ($type == 'hex') {
            return $hex;
        }

        return pack("H*", $hex);
    }

    /**
     * Return the hex string for this public key
     *
     * @return string
     */
    public function getPubKeyHex()
    {
        if ($this->isCompressed()) {
            $byte = self::getCompressedPrefix($this->getPoint());
            $xHex = Math::decHex($this->getX());
            $hex  = sprintf(
                "%s%s",
                $byte,
                str_pad($xHex, 64, '0', STR_PAD_LEFT)
            );

        } else {
            $xHex = Math::decHex($this->getX());
            $yHex = Math::decHex($this->getY());
            $hex  = sprintf(
                "%s%s%s",
                PublicKey::KEY_UNCOMPRESSED,
                str_pad($xHex, 64, '0', STR_PAD_LEFT),
                str_pad($yHex, 64, '0', STR_PAD_LEFT)
            );
        }

        return $hex;
    }

    /**
     * @inheritdoc
     */
    public function isPrivate()
    {
        return false;
    }

    /**
     * Return the prefix for an address, based on the point.
     *
     * @param PointInterface $point
     * @return string
     */
    public static function getCompressedPrefix(\Mdanter\Ecc\PointInterface $point)
    {
        return (Math::mod($point->getY(), 2) == '0')
            ? PublicKey::KEY_COMPRESSED_EVEN
            : PublicKey::KEY_COMPRESSED_ODD;
    }

    /**
     * Return the hex representation of the public key
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getPubKeyHex();
    }

    /**
     * Return the size of the serialized public key. Can choose hex type, or
     * binary (default)
     *
     * @param null $type
     * @return float|int
     */
    public function getSize($type = null)
    {
        $hex = $this->getPubKeyHex();

        if ($type == 'hex') {
            return strlen($hex);
        } else {
            return strlen($hex) / 2;
        }
    }

    /**
     * Sets a public key to be compressed
     *
     * @param $compressed
     * @return $this
     * @throws \Exception
     */
    public function setCompressed($compressed)
    {
        if (!is_bool($compressed)) {
            throw new \Exception('Compressed flag must be a boolean');
        }

        $this->compressed = $compressed;
        return $this;
    }

    /**
     * Compress a point
     *
     * @param $data
     * @return string
     * @throws \Exception
     */
    public static function compress($data)
    {
        if ($data instanceof \Mdanter\Ecc\PointInterface) {
            $point = $data;
        } elseif ($data instanceof PublicKeyInterface) {
            $point = $data->getPoint();
        } else {
            throw new \Exception('Parameter to compress() must be a PointInterface or PublicKeyInterface');
        }

        $byte = self::getCompressedPrefix($point);
        $xHex    = Math::decHex($point->getX());

        return sprintf(
            "%s%s",
            $byte,
            str_pad($xHex, 64, '0', STR_PAD_LEFT)
        );
    }

    /**
     * Recover Y from X and a parity byte
     * @param $xCoord
     * @param $byte
     * @param GeneratorPoint $generator
     * @throws \Exception
     */
    public static function recoverYfromX($xCoord, $byte, GeneratorPoint $generator = null)
    {
        if (! in_array($byte, array(PublicKey::KEY_COMPRESSED_ODD, PUBLICKEY::KEY_COMPRESSED_EVEN))) {
            throw new \RuntimeException('Incorrect byte for a public key');
        }

        if ($generator == null) {
            $generator = EccFactory::getSecgCurves()->generator256k1();
        }

        $curve = $generator->getCurve();

        try {
            // x ^ 3
            $xCubed = Math::powMod($xCoord, 3, $curve->getPrime());
            $ySquared = Math::add($xCubed, $curve->getB());

            // Calculate first root
            $root0 = NumberTheory::squareRootModP($ySquared, $curve->getPrime());

            if ($root0 == null) {
                throw new \RuntimeException('Unable to calculate sqrt mod p');
            }

            // Depending on the byte, we expect the Y value to be even or odd.
            // We only calculate the second y root if it's needed.
            if ($byte == PublicKey::KEY_COMPRESSED_EVEN) {
                $yCoord = (Math::mod($root0, 2) == '0')
                    ? $root0
                    : Math::sub($curve->getPrime(), $root0);
            } else {
                $yCoord = (Math::mod($root0, 2) !== '0')
                    ? $root0
                    : Math::sub($curve->getPrime(), $root0);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $yCoord;
    }

    /**
     * Generate public key from Hex
     *
     * @param $hex
     * @param GeneratorPoint $generator
     * @return PublicKey
     * @throws \Exception
     */
    public static function fromHex($hex, GeneratorPoint $generator = null)
    {
        $byte = substr($hex, 0, 2);

        if ($generator == null) {
            $generator = EccFactory::getSecgCurves()->generator256k1();
        }

        if (strlen($hex) == PublicKey::LENGTH_COMPRESSED) {
            $compressed = true;
            $xCoord = Math::hexDec(substr($hex, 2, 64));
            $yCoord = self::recoverYfromX($xCoord, $byte, $generator);

        } elseif (strlen($hex) == PublicKey::LENGTH_UNCOMPRESSED) {
            $compressed = false;
            $xCoord = Math::hexDec(substr($hex, 2, 64));
            $yCoord = Math::hexDec(substr($hex, 66, 64));

        } else {
            throw new \Exception('Invalid hex string, must match size of compressed or uncompressed public key');
        }

        $point = new Point($xCoord, $yCoord, $generator);

        return new self($point, $compressed);
    }
}
