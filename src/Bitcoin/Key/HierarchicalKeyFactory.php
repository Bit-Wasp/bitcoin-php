<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;

use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\HexExtendedKeySerializer;

class HierarchicalKeyFactory
{
    /**
     * @param EcAdapterInterface|null $ecAdapter
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
        $buffer  = PrivateKeyFactory::create(true, $ecAdapter);
        $private = self::fromEntropy($buffer->getBuffer()->getHex(), $ecAdapter);
        return $private;
    }

    /**
     * @param string $entropy
     * @param EcAdapterInterface $ecAdapter
     * @return HierarchicalKey
     */
    public static function fromEntropy($entropy, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $hash = Hash::hmac('sha512', pack("H*", $entropy), "Bitcoin seed");
        return new HierarchicalKey(
            $ecAdapter,
            0,
            0,
            0,
            $ecAdapter->getMath()->hexDec(substr($hash, 64, 64)),
            PrivateKeyFactory::fromHex(substr($hash, 0, 64), true, $ecAdapter)
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
