<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 20/11/14
 * Time: 05:18
 */

namespace Bitcoin;

use \Mdanter\Ecc\EccFactory;

class PrivateKey implements KeyInterface, PrivateKeyInterface
{

    protected $decimal;
    protected $hex;
    protected $curve;
    protected $publicKey;

    public function __construct(\Mdanter\Ecc\CurveFp $curve, $hex, $compressed = false)
    {
        $this->hex = $hex;
        $this->decimal = Math::hexDec($hex);
        $this->compressed = $compressed;

        if ($curve == null) {
            $curve = EccFactory::getSecgCurves()->secp256k1_curve();
        }

        $this->curve = $curve;
    }

    public function isCompressed()
    {
        return $this->compressed;
    }

    public function getCurve()
    {
        return $this->curve;
    }

    public function getHex()
    {
        return $this->hex;
    }

    public function getDec()
    {
        return $this->dec;
    }

    public function getWif(NetworkInterface $network, $compressed = false)
    {
        $byte = $network->getPrivByte();
        $hex = sprintf("%s%s", $byte, $this->hex);

        if ($compressed) {
            $hex .= '01';
        }

        return Base58::encode_check($hex);
    }

} 