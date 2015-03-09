<?php

namespace Afk11\Bitcoin\Key;

use \Afk11\Bitcoin\Bitcoin;
use \Afk11\Bitcoin\Math\Math;
use \Afk11\Bitcoin\Crypto\Hash;
use \Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Serializer\Key\PublicKey\HexPublicKeySerializer;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\PointInterface;
use Mdanter\Ecc\GeneratorPoint;

class PublicKey implements PublicKeyInterface
{
    /**
     * @var Math
     */
    protected $math;

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
        $this->math = Bitcoin::getMath();
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
     * @return mixed|string
     */
    public function getPubKeyHash()
    {
        $publicKey = $this->serialize('hex');
        $hash      = Hash::sha256ripe160($publicKey);
        return $hash;
    }

    /**
     * @param Buffer $publicKey
     * @return bool
     */
    public static function isCompressedOrUncompressed(Buffer $publicKey)
    {
        $vchPubKey = $publicKey->serialize();
        if ($publicKey->getSize() < 33) {
            return false;
        }

        if (ord($vchPubKey[0]) == 0x04) {
            if ($publicKey->getSize() != 65) {
                // Invalid length for uncompressed key
                return false;
            }
        } elseif (in_array($vchPubKey[0], array(
            PublicKey::KEY_COMPRESSED_EVEN,
            PublicKey::KEY_COMPRESSED_ODD))) {
            if ($publicKey->getSize() != 33) {
                return false;
            }
        } else {
            return false;
        }

        return true;
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
        if ($data instanceof PointInterface) {
            $point = $data;
        } elseif ($data instanceof PublicKeyInterface) {
            $point = $data->getPoint();
        } else {
            throw new \Exception('Parameter to compress() must be a PointInterface or PublicKeyInterface');
        }

        $byte  = self::getCompressedPrefix($point);
        $xHex  = Bitcoin::getMath()->decHex($point->getX());

        return sprintf(
            "%s%s",
            $byte,
            str_pad($xHex, 64, '0', STR_PAD_LEFT)
        );
    }

    /**
     * Return the prefix for an address, based on the point.
     *
     * @param PointInterface $point
     * @return string
     */
    public static function getCompressedPrefix(PointInterface $point)
    {
        return Bitcoin::getMath()->isEven($point->getY())
            ? PublicKey::KEY_COMPRESSED_EVEN
            : PublicKey::KEY_COMPRESSED_ODD;
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
     * @return bool
     */
    public function isCompressed()
    {
        return $this->compressed;
    }

    /**
     * @inheritdoc
     */
    public function isPrivate()
    {
        return false;
    }

    /**
     * Recover Y from X and a parity byte
     * @param $xCoord
     * @param $byte
     * @return int|string
     * @throws \Exception
     */
    public static function recoverYfromX($xCoord, $byte)
    {
        if (! in_array($byte, array(PublicKey::KEY_COMPRESSED_ODD, PUBLICKEY::KEY_COMPRESSED_EVEN))) {
            throw new \RuntimeException('Incorrect byte for a public key');
        }

        $math   = Bitcoin::getMath();
        $theory = $math->getNumberTheory();
        $generator  = Bitcoin::getGenerator();
        $curve = $generator->getCurve();

        try {
            // x ^ 3
            $xCubed   = $math->powMod($xCoord, 3, $curve->getPrime());
            $ySquared = $math->add($xCubed, $curve->getB());

            // Calculate first root
            $root0 = $theory->squareRootModP($ySquared, $curve->getPrime());

            if ($root0 == null) {
                throw new \RuntimeException('Unable to calculate sqrt mod p');
            }

            // Depending on the byte, we expect the Y value to be even or odd.
            // We only calculate the second y root if it's needed.
            if ($byte == PublicKey::KEY_COMPRESSED_EVEN) {
                $yCoord = ($math->isEven($root0))
                    ? $root0
                    : $math->sub($curve->getPrime(), $root0);
            } else {
                $yCoord = (!$math->isEven($root0))
                    ? $root0
                    : $math->sub($curve->getPrime(), $root0);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $yCoord;
    }

    /**
     * @return string
     */
    public function toHex()
    {
        $serializer = new HexPublicKeySerializer($this->math, Bitcoin::getGenerator());
        $hex = $serializer->serialize($this);
        return $hex;
    }

    /**
     * Serialize this according to requested type
     *
     * @param string|null $type
     * @return string
     */
    public function serialize($type = null)
    {
        $hex = $this->toHex();

        if ($type == 'hex') {
            return $hex;
        }

        return pack("H*", $hex);
    }

    /**
     * Return the hex representation of the public key
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toHex();
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
        $hex = $this->toHex();

        if ($type == 'hex') {
            return strlen($hex);
        } else {
            return strlen($hex) / 2;
        }
    }
}
