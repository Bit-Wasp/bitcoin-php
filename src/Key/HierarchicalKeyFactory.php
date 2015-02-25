<?php

namespace Afk11\Bitcoin\Key;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Exceptions\Base58ChecksumFailure;
use Afk11\Bitcoin\Exceptions\InvalidPrivateKey;
use Afk11\Bitcoin\NetworkInterface;
use Afk11\Bitcoin\Crypto\Hash;
use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Base58;

class HierarchicalKeyFactory
{

    public static function generateMasterKey(NetworkInterface $network)
    {
        $buffer  = PrivateKeyFactory::generate();
        $private = self::fromEntropy($buffer->serialize('hex'), $network);
        return $private;
    }

    public static function fromEntropy($entropy, NetworkInterface $network)
    {
        $math = Bitcoin::getMath();
        $hash = Hash::hmac('sha512', pack("H*", $entropy), "Bitcoin seed");

        $private = substr($hash, 0, 64);
        $chainCode = substr($hash, 64, 64);

        $privateDec = $math->hexDec($private);

        if (PrivateKey::isValidKey($privateDec) === false) {
            throw new InvalidPrivateKey("Entropy produced an invalid key.. Odds of this happening are very low.");
        }

        $bytes = new Parser();
        $bytes = $bytes->writeBytes(4, $network->getHDPrivByte())
            ->writeInt(1, '0')
            ->writeBytes(4, Buffer::hex('00000000'))
            ->writeBytes(4, '00000000')
            ->writeBytes(32, $chainCode)
            ->writeBytes(33, '00' . $private)
            ->getBuffer()
            ->serialize('hex');

        return new HierarchicalKey($bytes, $network);
    }

    /**
     * @param $extendedKey
     * @param NetworkInterface $network
     * @return HierarchicalKey
     * @throws Base58ChecksumFailure
     */
    public static function fromExtended($extendedKey, NetworkInterface $network = null)
    {
        $network ?: Bitcoin::getNetwork();

        try {
            $bytes = Base58::decodeCheck($extendedKey);
        } catch (Base58ChecksumFailure $e) {
            throw new Base58ChecksumFailure('Failed to decode HierarchicalKey');
        }

        return new HierarchicalKey($bytes, $network);
    }

    /**
     * @param HierarchicalKey $key
     * @return string
     */
    public static function toExtendedKey(HierarchicalKey $key)
    {
        return $key->isPrivate()
            ? self::toExtendedPrivateKey($key)
            : self::toExtendedPublicKey($key);
    }

    /**
     * @param HierarchicalKey $key
     * @return string
     */
    public static function toExtendedPublicKey(HierarchicalKey $key)
    {
        $hex = PublicKeyFactory::toHex($key->getPublicKey());

        $bytes = new Parser();
        $bytes = $bytes
            ->writeBytes(4, $key->getNetwork()->getHDPrivByte())
            ->writeInt(1, $key->getDepth())
            ->writeBytes(4, $key->getFingerprint())
            ->writeInt(4, $key->getSequence())
            ->writeBytes(32, $key->getChainCode()->serialize('hex'))
            ->writeBytes(33, $hex)
            ->getBuffer()
            ->serialize('hex');

        $base58 = Base58::encodeCheck($bytes);

        return $base58;
    }

    /**
     * @param HierarchicalKey $key
     * @return string
     * @throws \Exception
     */
    public static function toExtendedPrivateKey(HierarchicalKey $key)
    {
        $hex = PrivateKeyFactory::toHex($key->getPrivateKey());

        $bytes = new Parser();
        $bytes = $bytes
            ->writeBytes(4, $key->getNetwork()->getHDPrivByte())
            ->writeInt(1, $key->getDepth())
            ->writeBytes(4, $key->getFingerprint())
            ->writeInt(4, $key->getSequence())
            ->writeBytes(32, $key->getChainCode()->serialize('hex'))
            ->writeBytes(1, '00')
            ->writeBytes(32, $hex)
            ->getBuffer()
            ->serialize('hex');

        $base58 = Base58::encodeCheck($bytes);

        return $base58;
    }
}