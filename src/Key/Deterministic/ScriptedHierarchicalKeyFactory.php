<?php

namespace BitWasp\Bitcoin\Key\Deterministic;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptDataFactoryInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey\Base58ScriptedExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey\ExtendedKeyWithScriptSerializer;
use BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey\GlobalHdKeyPrefixConfig;
use BitWasp\Buffertools\Buffer;
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
     * @return ScriptedHierarchicalKey
     * @throws \Exception
     */
    public static function generateMasterKey(ScriptDataFactoryInterface $scriptDataFactory, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $buffer = PrivateKeyFactory::create(true, $ecAdapter);
        return self::fromEntropy($buffer->getBuffer(), $scriptDataFactory, $ecAdapter);
    }

    /**
     * @param BufferInterface $entropy
     * @param ScriptDataFactoryInterface $scriptDataFactory
     * @param EcAdapterInterface|null $ecAdapter
     * @return ScriptedHierarchicalKey
     * @throws \Exception
     */
    public static function fromEntropy(BufferInterface $entropy, ScriptDataFactoryInterface $scriptDataFactory, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $seed = Hash::hmac('sha512', $entropy, new Buffer('Bitcoin seed', null, $ecAdapter->getMath()));
        $privateKey = PrivateKeyFactory::fromHex($seed->slice(0, 32), true, $ecAdapter);
        return new ScriptedHierarchicalKey($ecAdapter, $scriptDataFactory, 0, 0, 0, $seed->slice(32, 32), $privateKey);
    }

    /**
     * @param string $extendedKey
     * @param GlobalHdKeyPrefixConfig $config
     * @param NetworkInterface $network
     * @param EcAdapterInterface $ecAdapter
     * @return ScriptedHierarchicalKey
     */
    public static function fromExtended($extendedKey, GlobalHdKeyPrefixConfig $config, NetworkInterface $network = null, EcAdapterInterface $ecAdapter = null)
    {
        $extSerializer = self::getSerializer($ecAdapter ?: Bitcoin::getEcAdapter(), $config);
        return $extSerializer->parse($network ?: Bitcoin::getNetwork(), $extendedKey);
    }
}
