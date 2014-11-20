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
    /**
     * @var
     */
    protected $decimal;

    /**
     * @var
     */
    protected $hex;

    /**
     * @var \Mdanter\Ecc\CurveFp
     */
    protected $curve;

    /**
     * @var
     */
    protected $publicKey;

    /**
     * @param \Mdanter\Ecc\CurveFp $curve
     * @param $hex
     * @param bool $compressed
     */
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

    /**
     * @inheritdoc
     */
    public function isCompressed()
    {
        return $this->compressed;
    }

    /**
     * Set whether compressed keys should be returned
     *
     * @param $setting
     */
    public function setCompressed($setting)
    {
        $this->compressed = $setting;
    }

    /**
     * @inheritdoc
     */
    public function getCurve()
    {
        return $this->curve;
    }

    /**
     * @inheritdoc
     */
    public function getHex()
    {
        return $this->hex;
    }

    /**
     * @inheritdoc
     */
    public function getDec()
    {
        return $this->dec;
    }

    /**
     * When given a network,
     * @param NetworkInterface $network
     * @param bool $compressed
     * @return string
     */
    public function getWif(NetworkInterface $network)
    {
        $byte = $network->getPrivByte();
        $hex = sprintf("%s%s", $byte, $this->hex);

        if ($this->isCompressed()) {
            $hex .= '01';
        }

        return Base58::encode_check($hex);
    }

} 