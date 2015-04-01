<?php

namespace BitWasp\Bitcoin\Serializer\Key\PublicKey;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PublicKey;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Parser;
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
     * @param $compressed
     * @param PointInterface $point
     * @return string
     */
    public function getPrefix($compressed, PointInterface $point)
    {
        return $compressed
            ? $this->ecAdapter->getMath()->isEven($point->getY())
                ? PublicKey::KEY_COMPRESSED_EVEN
                : PublicKey::KEY_COMPRESSED_ODD
            : PublicKey::KEY_UNCOMPRESSED;
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @return Buffer
     */
    public function serialize(PublicKeyInterface $publicKey)
    {
        $point = $publicKey->getPoint();
        $math = $this->ecAdapter->getMath();
        $parser = new Parser();
        $parser->writeBytes(1, $this->getPrefix($publicKey->isCompressed(), $point));

        $publicKey->isCompressed()
            ? $parser
                ->writeBytes(32, str_pad($math->decHex($point->getX()), 64, '0', STR_PAD_LEFT))
            : $parser
                ->writeBytes(32, str_pad($math->decHex($point->getX()), 64, '0', STR_PAD_LEFT))
                ->writeBytes(32, str_pad($math->decHex($point->getY()), 64, '0', STR_PAD_LEFT));

        return $parser->getBuffer();
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
