<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\Deterministic;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2pkhScriptDataFactory;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptDataFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\Base58ExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class HierarchicalKeyFactory
{
    /**
     * @param EcAdapterInterface $ecAdapter
     * @return Base58ExtendedKeySerializer
     */
    public static function getSerializer(EcAdapterInterface $ecAdapter): Base58ExtendedKeySerializer
    {
        $extSerializer = new Base58ExtendedKeySerializer(new ExtendedKeySerializer($ecAdapter));
        return $extSerializer;
    }

    /**
     * @param EcAdapterInterface|null $ecAdapter
     * @param ScriptDataFactory|null $scriptDataFactory
     * @return HierarchicalKey
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     * @throws \Exception
     */
    public static function generateMasterKey(EcAdapterInterface $ecAdapter = null, ScriptDataFactory $scriptDataFactory = null): HierarchicalKey
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $buffer = PrivateKeyFactory::create(true, $ecAdapter);
        return self::fromEntropy($buffer->getBuffer(), $ecAdapter, $scriptDataFactory);
    }

    /**
     * @param BufferInterface $entropy
     * @param ScriptDataFactory|null $scriptFactory
     * @return HierarchicalKey
     * @throws \Exception
     */
    public static function fromEntropy(BufferInterface $entropy, EcAdapterInterface $ecAdapter = null, ScriptDataFactory $scriptFactory = null): HierarchicalKey
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $scriptFactory = $scriptFactory ?: new P2pkhScriptDataFactory(EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter));
        $seed = Hash::hmac('sha512', $entropy, new Buffer('Bitcoin seed', null, $ecAdapter->getMath()));
        $privateKey = PrivateKeyFactory::fromBuffer($seed->slice(0, 32), true, $ecAdapter);
        return new HierarchicalKey($ecAdapter, $scriptFactory, 0, 0, 0, $seed->slice(32, 32), $privateKey);
    }

    /**
     * @param string $extendedKey
     * @param NetworkInterface $network
     * @param EcAdapterInterface $ecAdapter
     * @return HierarchicalKey
     */
    public static function fromExtended($extendedKey, NetworkInterface $network = null, EcAdapterInterface $ecAdapter = null): HierarchicalKey
    {
        $extSerializer = self::getSerializer($ecAdapter ?: Bitcoin::getEcAdapter());
        return $extSerializer->parse($network ?: Bitcoin::getNetwork(), $extendedKey);
    }
}
