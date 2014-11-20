<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 20/11/14
 * Time: 05:18
 */

namespace Bitcoin;

use \Mdanter\Ecc\EccFactory;
use \Mdanter\Ecc\PointInterface;
use \Mdanter\Ecc\GeneratorPoint;

class PublicKey implements KeyInterface, PublicKeyInterface
{

    const PARITYBYTE_EVEN = '02';
    const PARITYBYTE_ODD = '03';

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
    public function __construct(\Mdanter\Ecc\PointInterface $point, $compressed = FALSE)
    {
        $this->point = $point;
        $this->compressed = $compressed;
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
     * Get Hex representation of this
     * @return string
     */
    public function getHex($binary_output = FALSE)
    {
        $hex = '';
        if ($this->compressed) {
            $byte = (Math::mod($this->getY(), 2) == '0') ? '02' : '03';
            $x    = str_pad(Math::decHex($this->getX()), '0', 64, STR_PAD_LEFT);
            $hex  = $byte . $x;

        } else {
            $x    = Math::decHex($this->getX());
            $y    = Math::decHex($this->getY());
            $hex  = '04' . $x . $y;
        }

        if ($binary_output) {
            return hex2bin($hex);
        }

        return $hex;
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
        if ($data instanceof PointInterface) {
            $point = $data;
        } elseif ($data instanceof PublicKeyInterface) {
            $point = $data->getPoint();
        } else {
            throw new \Exception('Parameter to compress() must be a PointInterface or PublicKeyInterface');
        }

        $parity = Math::mod($point->getY(), 2);
        $byte = ($parity == 0) ? self::PARITYBYTE_EVEN : self::PARITYBYTE_ODD;

        return sprintf(
            "%s%s",
            $byte,
            Math::decHex($point->getX())
        );
    }

    /**
     * Recover Y from X and a parity byte
     * @param $x
     * @param $byte
     * @param GeneratorPoint $generator
     * @throws \Exception
     */
    public static function recoverYFromX($x, $byte, GeneratorPoint $generator = null)
    {
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

            // Second root
            $y1 = Math::sub($curve->getPrime(), $y0);

            if ($byte == PublicKey::PARITYBYTE_EVEN) {
                $y = (Math::mod($y0, 2) == '0') ? $y0 : $y1;
            } else if ($byte == PublicKey::PARITYBYTE_ODD) {
                $y = (Math::mod($y0, 2) !== '0') ? $y0 : $y1;
            } else {
                throw new \RuntimeException('Incorrect byte for a public key');
            }

            return $y;
        }
        catch (\Exception $e)
        {
            throw $e;
        }
    }

    /**
     * Generate public key from Hex
     *
     * @param $hex
     * @param GeneratorPoint $generator
     * @return PublicKey
     * @throws
     * @throws \Exception
     */
    public static function fromHex($hex, GeneratorPoint $generator = null)
    {
        $byte = substr($hex, 0, 2);

        if (strlen($hex) == 66) {
            $compressed = true;
            $x = Math::hexDec(substr($hex, 2, 64));
            $y = Point::recoverYfromX($x, $byte, 2);

        } elseif (strlen($hex) == 130) {
            $compressed = false;
            $x = Math::hexDec(substr($hex, 2, 64));
            $y = Math::hexDec(substr($hex, 66, 64));

        } else {
            throw \Exception('Invalid hex string, must match size of compressed or uncompressed public key');
        }

        $point = new Point($x, $y, $generator);
        return new self($point, $compressed);
    }

} 