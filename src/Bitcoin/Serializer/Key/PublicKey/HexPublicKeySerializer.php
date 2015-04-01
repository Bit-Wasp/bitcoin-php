<?php

namespace BitWasp\Bitcoin\Serializer\Key\PublicKey;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PublicKey;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use Mdanter\Ecc\PointInterface;

class HexPublicKeySerializer
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(EcAdapterInterface $ecAdapter)
    {
        $this->ecAdapter = $ecAdapter;
    }

    /**
     * Return the prefix for an address, based on the point.
     *
     * @param PointInterface $point
     * @return string
     */
    public function getCompressedPrefix(PointInterface $point)
    {
        return $this->ecAdapter->getMath()->isEven($point->getY())
            ? PublicKey::KEY_COMPRESSED_EVEN
            : PublicKey::KEY_COMPRESSED_ODD;
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
                $this->getCompressedPrefix($point),
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
        $hex = Buffer::hex($hex);
        if (!in_array($hex->getSize(), [PublicKey::LENGTH_COMPRESSED, PublicKey::LENGTH_UNCOMPRESSED])) {
            throw new \Exception('Invalid hex string, must match size of compressed or uncompressed public key');
        }

        return $this->ecAdapter->publicKeyFromBuffer($hex);
    }
}
