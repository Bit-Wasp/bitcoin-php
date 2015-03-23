<?php

namespace BitWasp\Bitcoin\Serializer\Key\PublicKey;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PublicKey;
use BitWasp\Bitcoin\Key\PublicKeyInterface;

class HexPublicKeySerializer
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @internal param Math $math
     */
    public function __construct(EcAdapterInterface $ecAdapter)
    {
        $this->ecAdapter = $ecAdapter;
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @return Buffer
     */
    public function serialize(PublicKeyInterface $publicKey)
    {
        $point = $publicKey->getPoint();
        $math = $this->ecAdapter->getMath();

        if ($publicKey->isCompressed()) {
            $binary = pack(
                "H2H64",
                PublicKey::getCompressedPrefix($point),
                str_pad($math->decHex($point->getX()), 64, '0', STR_PAD_LEFT)
            );

        } else {
            $binary = pack(
                "H2H64H64",
                PublicKey::KEY_UNCOMPRESSED,
                str_pad($math->decHex($point->getX()), 64, '0', STR_PAD_LEFT),
                str_pad($math->decHex($point->getY()), 64, '0', STR_PAD_LEFT)
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
        $math = $this->ecAdapter->getMath();
        $generator = $this->ecAdapter->getGenerator();
        $byte = substr($hex, 0, 2);

        if (strlen($hex) == PublicKey::LENGTH_COMPRESSED) {
            $compressed = true;
            $xCoord = $math->hexDec(substr($hex, 2, 64));
            $yCoord = PublicKey::recoverYfromX($xCoord, $byte, $generator);

        } elseif (strlen($hex) == PublicKey::LENGTH_UNCOMPRESSED) {
            $compressed = false;
            $xCoord = $math->hexDec(substr($hex, 2, 64));
            $yCoord = $math->hexDec(substr($hex, 66, 64));

        } else {
            throw new \Exception('Invalid hex string, must match size of compressed or uncompressed public key');
        }

        $point = $generator->getCurve()->getPoint($xCoord, $yCoord);

        return new PublicKey($this->ecAdapter, $point, $compressed);
    }
}
