<?php

namespace Afk11\Bitcoin\Serializer\Key\PublicKey;

use Afk11\Bitcoin\Buffer;
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
     * @return Buffer
     */
    public function serialize(PublicKeyInterface $publicKey)
    {
        $point = $publicKey->getPoint();

        if ($publicKey->isCompressed()) {
            $binary = pack(
                "H2H64",
                PublicKey::getCompressedPrefix($point),
                str_pad($this->math->decHex($point->getX()), 64, '0', STR_PAD_LEFT)
            );

        } else {
            $binary = pack(
                "H2H64H64",
                PublicKey::KEY_UNCOMPRESSED,
                str_pad($this->math->decHex($point->getX()), 64, '0', STR_PAD_LEFT),
                str_pad($this->math->decHex($point->getY()), 64, '0', STR_PAD_LEFT)
            );
        }

        $out = new Buffer($binary);
        return $out;
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

        return new PublicKey($this->math, $this->generator, $point, $compressed);
    }
}
