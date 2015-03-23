<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Exceptions\InvalidPrivateKey;
use BitWasp\Bitcoin\Serializer\Key\PrivateKey\HexPrivateKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\PrivateKey\WifPrivateKeySerializer;

class PrivateKeyFactory
{
    /**
     * Generate a buffer containing a valid key
     *
     * @return \BitWasp\Bitcoin\Buffer
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public static function generateSecret()
    {
        $random = new Random();

        do {
            $buffer = $random->bytes(32);
        } while (!PrivateKey::isValidKey($buffer->serialize('int')));

        return $buffer;
    }

    /**
     * @param $int
     * @param bool $compressed
     * @param EcAdapterInterface|null $ecAdapter
     * @return PrivateKey
     */
    public static function fromInt($int, $compressed = false, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $privateKey = new PrivateKey($ecAdapter, $int, $compressed);
        return $privateKey;
    }

    /**
     * @param bool $compressed
     * @param EcAdapterInterface|null $ecAdapter
     * @return PrivateKey
     */
    public static function create($compressed = false, EcAdapterInterface $ecAdapter = null)
    {
        $secret = self::generateSecret();
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        return self::fromInt($secret->serialize('int'), $compressed, $ecAdapter);
    }

    /**
     * @param $wif
     * @param EcAdapterInterface|null $ecAdapter
     * @return PrivateKey
     * @throws InvalidPrivateKey
     */
    public static function fromWif($wif, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $wifSerializer = new WifPrivateKeySerializer($ecAdapter->getMath(), new HexPrivateKeySerializer($ecAdapter));
        return $wifSerializer->parse($wif);
    }

    /**
     * @param string $hex
     * @param bool $compressed
     * @param EcAdapterInterface|null $ecAdapter
     * @return PrivateKey
     */
    public static function fromHex($hex, $compressed = false, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $hexSerializer = new HexPrivateKeySerializer($ecAdapter);
        return $hexSerializer->parse($hex)->setCompressed($compressed);
    }
}
