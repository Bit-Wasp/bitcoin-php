<?php

namespace BitWasp\Bitcoin\Key\Deterministic;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptDataFactoryInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey\Base58ScriptedExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey\ExtendedKeyWithScriptSerializer;
use BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey\GlobalHdKeyPrefixConfig;
use BitWasp\Buffertools\BufferInterface;

class ScriptedHierarchicalKeyFactory
{
    /**
     * @param EcAdapterInterface $ecAdapter
     * @param GlobalHdKeyPrefixConfig $hdPrefixConfig
     * @return Base58ScriptedExtendedKeySerializer
     */
    public static function getSerializer(EcAdapterInterface $ecAdapter, GlobalHdKeyPrefixConfig $hdPrefixConfig)
    {
        return new Base58ScriptedExtendedKeySerializer(
            new ExtendedKeyWithScriptSerializer($ecAdapter, $hdPrefixConfig)
        );
    }

    /**
     * @param ScriptDataFactoryInterface $scriptDataFactory
     * @param EcAdapterInterface|null $ecAdapter
     * @return HierarchicalKeyScriptDecorator
     * @throws \Exception
     */
    public static function generateMasterKey(ScriptDataFactoryInterface $scriptDataFactory, EcAdapterInterface $ecAdapter = null)
    {
        return new HierarchicalKeyScriptDecorator(
            $scriptDataFactory,
            HierarchicalKeyFactory::generateMasterKey($ecAdapter)
        );
    }

    /**
     * @param BufferInterface $entropy
     * @param ScriptDataFactoryInterface $scriptDataFactory
     * @param EcAdapterInterface|null $ecAdapter
     * @return HierarchicalKeyScriptDecorator
     * @throws \Exception
     */
    public static function fromEntropy(BufferInterface $entropy, ScriptDataFactoryInterface $scriptDataFactory, EcAdapterInterface $ecAdapter = null)
    {
        return new HierarchicalKeyScriptDecorator(
            $scriptDataFactory,
            HierarchicalKeyFactory::fromEntropy($entropy, $ecAdapter)
        );
    }

    /**
     * @param string $extendedKey
     * @param GlobalHdKeyPrefixConfig $config
     * @param NetworkInterface $network
     * @param EcAdapterInterface $ecAdapter
     * @return HierarchicalKeyScriptDecorator
     */
    public static function fromExtended($extendedKey, GlobalHdKeyPrefixConfig $config, NetworkInterface $network = null, EcAdapterInterface $ecAdapter = null)
    {
        $extSerializer = self::getSerializer($ecAdapter ?: Bitcoin::getEcAdapter(), $config);
        return $extSerializer->parse($network ?: Bitcoin::getNetwork(), $extendedKey);
    }
}
