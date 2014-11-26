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
        return $this->point->getCurve();
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
        $public_key = $this->serialize('hex');

        $hash = Hash::sha256ripe160($public_key);
        return $hash;
    }

    /**
     * Serialize this according to requested type
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
     * @return string
     */
    public function getPubKeyHex()
    {
        if ($this->isCompressed()) {
            $byte = self::getCompressedPrefix($this->getPoint());
            $x    = Math::decHex($this->getX());
            $hex  = sprintf(
                "%s%s",
                $byte,
                str_pad($x, 64, '0', STR_PAD_LEFT)
            );

        } else {
            $x    = Math::decHex($this->getX());
            $y    = Math::decHex($this->getY());
            $hex  = sprintf(
                "%s%s%s",
                PublicKey::KEY_UNCOMPRESSED,
                str_pad($x, 64, '0', STR_PAD_LEFT),
                str_pad($y, 64, '0', STR_PAD_LEFT)
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

    public static function getCompressedPrefix(\Mdanter\Ecc\PointInterface $point)
    {
        return (Math::mod($point->getY(), 2) == '0')
            ? PublicKey::KEY_COMPRESSED_EVEN
            : PublicKey::KEY_COMPRESSED_ODD;
    }

    public function __toString()
    {
        return $this->getPubKeyHex();
    }

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
        $x    = Math::decHex($point->getX());

        return sprintf(
            "%s%s",
            $byte,
            str_pad($x, 64, '0', STR_PAD_LEFT)
        );
    }

    /**
     * Recover Y from X and a parity byte
     * @param $x
     * @param $byte
     * @param GeneratorPoint $generator
     * @throws \Exception
     */
    public static function recoverYfromX($x, $byte, GeneratorPoint $generator = null)
    {
        if (! in_array($byte, [PublicKey::KEY_COMPRESSED_ODD, PUBLICKEY::KEY_COMPRESSED_EVEN])) {
            throw new \RuntimeException('Incorrect byte for a public key');
        }

        if ($generator == null) {
            $generator = EccFactory::getSecgCurves()->generator256k1();
        }

        $curve = $generator->getCurve();

        try {
            // x ^ 3
            $x3 = Math::powMod($x, 3, $curve->getPrime());
            $y2 = Math::add($x3, $curve->getB());

            // Calculate first root
            $y0 = NumberTheory::squareRootModP($y2, $curve->getPrime());

            if ($y0 == null) {
                throw new \RuntimeException('Unable to calculate sqrt mod p');
            }

            // Depending on the byte, we expect the Y value to be even or odd.
            // We only calculate the second y root if it's needed.
            if ($byte == PublicKey::KEY_COMPRESSED_EVEN) {
                $y = (Math::mod($y0, 2) == '0')
                    ? $y0
                    : Math::sub($curve->getPrime(), $y0);
            } else if ($byte == PublicKey::KEY_COMPRESSED_ODD) {
                $y = (Math::mod($y0, 2) !== '0')
                    ? $y0
                    : Math::sub($curve->getPrime(), $y0);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $y;
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
            $x = Math::hexDec(substr($hex, 2, 64));
            $y = self::recoverYfromX($x, $byte, $generator);

        } elseif (strlen($hex) == PublicKey::LENGTH_UNCOMPRESSED) {
            $compressed = false;
            $x = Math::hexDec(substr($hex, 2, 64));
            $y = Math::hexDec(substr($hex, 66, 64));

        } else {
            throw new \Exception('Invalid hex string, must match size of compressed or uncompressed public key');
        }

        $point = new Point($x, $y, $generator);

        return new self($point, $compressed);
    }
}
