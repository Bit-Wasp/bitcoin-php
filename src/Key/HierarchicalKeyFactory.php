<?php

namespace BitWasp\Bitcoin\Key;


use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\HexExtendedKeySerializer;
use BitWasp\Buffertools\Buffer;

class HierarchicalKeyFactory
{
    /**
     * @param EcAdapterInterface $ecAdapter
     * @param NetworkInterface $network
     * @return ExtendedKeySerializer
     */
    public static function getSerializer(EcAdapterInterface $ecAdapter, $network)
    {
        $extSerializer = new ExtendedKeySerializer(new HexExtendedKeySerializer($ecAdapter, $network));
        return $extSerializer;
    }

    /**
     * @param EcAdapterInterface|null $ecAdapter
     * @return HierarchicalKey
     */
    public static function generateMasterKey(EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $buffer = PrivateKeyFactory::create(true, $ecAdapter);
        return self::fromEntropy($buffer->getBuffer(), $ecAdapter);
    }

    /**
     * @param Buffer $entropy
     * @param EcAdapterInterface $ecAdapter
     * @return HierarchicalKey
     */
    public static function fromEntropy(Buffer $entropy, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $hash = Hash::hmac('sha512', $entropy, new Buffer("Bitcoin seed"), true);

        return new HierarchicalKey(
            $ecAdapter,
            0,
            0,
            0,
            $hash->slice(32, 32)->getInt(),
            PrivateKeyFactory::fromHex($hash->slice(0, 32)->getHex(), true, $ecAdapter)
        );
    }

    /**
     * @param $extendedKey
     * @param NetworkInterface $network
     * @param EcAdapterInterface $ecAdapter
     * @return HierarchicalKey
     */
    public static function fromExtended($extendedKey, NetworkInterface $network, EcAdapterInterface $ecAdapter = null)
    {
        $extSerializer = self::getSerializer($ecAdapter ?: Bitcoin::getEcAdapter(), $network);
        return $extSerializer->parse($extendedKey);
    }
}
