<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Serializer\Key\PublicKey\HexPublicKeySerializer;
use Mdanter\Ecc\PointInterface;

class PublicKey extends Key implements PublicKeyInterface
{
    /**
     * @var EcAdapterInterface
     */
    protected $ecAdapter;

    /**
     * @var PointInterface
     */
    protected $point;

    /**
     * @var bool
     */
    protected $compressed;

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
     * @return mixed|string
     */
    public function getPubKeyHash()
    {
        $publicKey = $this->getBuffer()->getHex();
        $hash      = Hash::sha256ripe160($publicKey);
        return $hash;
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
            hex2bin(PublicKey::KEY_COMPRESSED_EVEN),
            hex2bin(PublicKey::KEY_COMPRESSED_ODD)))) {
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

    /**
     * Return the hex representation of the public key
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getBuffer()->getHex();
    }
}
