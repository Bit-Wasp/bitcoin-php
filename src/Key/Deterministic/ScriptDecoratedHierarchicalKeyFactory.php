<?php

namespace BitWasp\Bitcoin\Key\Deterministic;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptDataFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\ScriptDecoratedHierarchicalKey\Base58ExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\ScriptDecoratedHierarchicalKey\ExtendedKeySerializer;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\GlobalPrefixConfig;
use BitWasp\Buffertools\BufferInterface;

class ScriptDecoratedHierarchicalKeyFactory
{
    /**
     * @param EcAdapterInterface $ecAdapter
     * @param GlobalPrefixConfig $hdPrefixConfig
     * @return Base58ExtendedKeySerializer
     */
    public static function getSerializer(EcAdapterInterface $ecAdapter, GlobalPrefixConfig $hdPrefixConfig)
    {
        return new Base58ExtendedKeySerializer(
            new ExtendedKeySerializer($ecAdapter, $hdPrefixConfig)
        );
    }

    /**
     * @param ScriptDataFactory $scriptDataFactory
     * @param EcAdapterInterface|null $ecAdapter
     * @return HierarchicalKeyScriptDecorator
     * @throws \Exception
     */
    public static function generateMasterKey(ScriptDataFactory $scriptDataFactory, EcAdapterInterface $ecAdapter = null)
    {
        return new HierarchicalKeyScriptDecorator(
            $scriptDataFactory,
            HierarchicalKeyFactory::generateMasterKey($ecAdapter)
        );
    }

    /**
     * @param BufferInterface $entropy
     * @param ScriptDataFactory $scriptDataFactory
     * @param EcAdapterInterface|null $ecAdapter
     * @return HierarchicalKeyScriptDecorator
     * @throws \Exception
     */
    public static function fromEntropy(BufferInterface $entropy, ScriptDataFactory $scriptDataFactory, EcAdapterInterface $ecAdapter = null)
    {
        return new HierarchicalKeyScriptDecorator(
            $scriptDataFactory,
            HierarchicalKeyFactory::fromEntropy($entropy, $ecAdapter)
        );
    }

    /**
     * @param string $extendedKey
     * @param GlobalPrefixConfig $config
     * @param NetworkInterface $network
     * @param EcAdapterInterface $ecAdapter
     * @return HierarchicalKeyScriptDecorator
     */
    public static function fromExtended($extendedKey, GlobalPrefixConfig $config, NetworkInterface $network = null, EcAdapterInterface $ecAdapter = null)
    {
        $extSerializer = self::getSerializer($ecAdapter ?: Bitcoin::getEcAdapter(), $config);
        return $extSerializer->parse($network ?: Bitcoin::getNetwork(), $extendedKey);
    }
}
