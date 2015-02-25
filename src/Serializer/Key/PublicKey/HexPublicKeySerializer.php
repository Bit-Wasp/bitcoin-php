<?php

namespace Afk11\Bitcoin\Serializer\Key\PublicKey;

use Afk11\Bitcoin\Key\PublicKey;
use Afk11\Bitcoin\Key\PublicKeyInterface;
use Afk11\Bitcoin\Math\Math;
use Mdanter\Ecc\GeneratorPoint;

class HexPublicKeySerializer
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var GeneratorPoint
     */
    private $generator;

    /**
     * @param Math $math
     * @param GeneratorPoint $G
     */
    public function __construct(Math $math, GeneratorPoint $generator)
    {
        $this->math = $math;
        $this->generator = $generator;
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @return string
     */
    public function serialize(PublicKeyInterface $publicKey)
    {
        $point = $publicKey->getPoint();

        if ($publicKey->isCompressed()) {
            $xHex = $this->math->decHex($point->getX());
            $hex  = sprintf(
                "%s%s",
                PublicKey::getCompressedPrefix($point),
                str_pad($xHex, 64, '0', STR_PAD_LEFT)
            );

        } else {
            $xHex = $this->math->decHex($point->getX());
            $yHex = $this->math->decHex($point->getY());
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
     * @param $hex
     * @return PublicKey
     * @throws \Exception
     */
    public function parse($hex)
    {
        $byte = substr($hex, 0, 2);

        if (strlen($hex) == PublicKey::LENGTH_COMPRESSED) {
            $compressed = true;
            $xCoord = $this->math->hexDec(substr($hex, 2, 64));
            $yCoord = PublicKey::recoverYfromX($xCoord, $byte, $this->generator);

        } elseif (strlen($hex) == PublicKey::LENGTH_UNCOMPRESSED) {
            $compressed = false;
            $xCoord = $this->math->hexDec(substr($hex, 2, 64));
            $yCoord = $this->math->hexDec(substr($hex, 66, 64));

        } else {
            throw new \Exception('Invalid hex string, must match size of compressed or uncompressed public key');
        }

        $point = $this->generator->getCurve()->getPoint($xCoord, $yCoord);

        return new PublicKey($point, $compressed);
    }
}
