<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Serializer\Key\PublicKey\HexPublicKeySerializer;
use Mdanter\Ecc\Primitives\PointInterface;

class PublicKey extends Key implements PublicKeyInterface
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var PointInterface
     */
    private $point;

    /**
     * @var bool
     */
    private $compressed;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param PointInterface $point
     * @param bool $compressed
     */
    public function __construct(
        EcAdapterInterface $ecAdapter,
        PointInterface $point,
        $compressed = false
    ) {
        $this->ecAdapter = $ecAdapter;
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
     * @return Buffer
     */
    public function getPubKeyHash()
    {
        return Hash::sha256ripe160($this->getBuffer());
    }

    /**
     * @param int $tweak
     * @return PublicKeyInterface
     */
    public function tweakAdd($tweak)
    {
        return $this->ecAdapter->publicKeyAdd($this, $tweak);
    }

    /**
     * @param int $tweak
     * @return PublicKeyInterface
     */
    public function tweakMul($tweak)
    {
        return $this->ecAdapter->publicKeyMul($this, $tweak);
    }

    /**
     * @param Buffer $publicKey
     * @return bool
     */
    public static function isCompressedOrUncompressed(Buffer $publicKey)
    {
        $vchPubKey = $publicKey->getBinary();
        if ($publicKey->getSize() < 33) {
            return false;
        }

        if (ord($vchPubKey[0]) == 0x04) {
            if ($publicKey->getSize() != 65) {
                // Invalid length for uncompressed key
                return false;
            }
        } elseif (in_array($vchPubKey[0], array(
            hex2bin(self::KEY_COMPRESSED_EVEN),
            hex2bin(self::KEY_COMPRESSED_ODD)))) {
            if ($publicKey->getSize() != 33) {
                return false;
            }
        } else {
            return false;
        }

        return true;
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
     * @return Buffer
     */
    public function getBuffer()
    {
        $serializer = new HexPublicKeySerializer($this->ecAdapter);
        $hex = $serializer->serialize($this);
        return $hex;
    }
}
