<?php

namespace BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;
use BitWasp\Bitcoin\Key\Deterministic\ScriptedHierarchicalKey;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\RawKeyParams;
use BitWasp\Buffertools\Buffer;

class KeyWithScriptAdapter
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var GlobalHdKeyPrefixConfig
     */
    private $config;

    /**
     * BasicBip32Key constructor.
     * @param GlobalHdKeyPrefixConfig $config
     * @param EcAdapterInterface|null $ecAdapter
     */
    public function __construct(
        GlobalHdKeyPrefixConfig $config,
        EcAdapterInterface $ecAdapter = null
    ) {
        if (null === $ecAdapter) {
            $ecAdapter = Bitcoin::getEcAdapter();
        }

        $this->ecAdapter = $ecAdapter;
        $this->config = $config;
    }

    /**
     * @param Network $network
     * @param RawKeyParams $params
     * @return HierarchicalKey
     * @throws \Exception
     */
    public function getKey(Network $network, RawKeyParams $params)
    {
        $scriptConfig = $this->config
            ->getNetworkHdPrefixConfig($network)
            ->getConfigForPrefix($params->getPrefix())
        ;

        if ($params->getPrefix() === $scriptConfig->getPublicPrefix()) {
            $key = PublicKeyFactory::fromHex($params->getKeyData(), $this->ecAdapter);
        } else if ($params->getPrefix() === $scriptConfig->getPrivatePrefix()) {
            $key = PrivateKeyFactory::fromHex($params->getKeyData()->slice(1), true, $this->ecAdapter);
        } else {
            throw new \InvalidArgumentException('Invalid prefix for extended key');
        }

        return new ScriptedHierarchicalKey(
            $this->ecAdapter,
            $scriptConfig->getScriptDataFactory(),
            $params->getDepth(),
            $params->getFingerprint(),
            $params->getSequence(),
            $params->getChainCode(),
            $key
        );
    }

    /**
     * get params from key, so we can serialize
     * need to
     * @param NetworkInterface $network
     * @param ScriptedHierarchicalKey $key
     * @return RawKeyParams
     * @throws \Exception
     */
    public function getParams(NetworkInterface $network, ScriptedHierarchicalKey $key)
    {
        if (!($key instanceof ScriptedHierarchicalKey)) {
            throw new \InvalidArgumentException("May only use ScriptedHierarchicalKey with KeyWithScriptAdapter");
        }

        $scriptConfig = $this->config
            ->getNetworkHdPrefixConfig($network)
            ->getConfigForScriptType($key->getScriptDataFactory()->getScriptType())
        ;

        if ($key->isPrivate()) {
            $prefix = $scriptConfig->getPrivatePrefix();
            $keyData = new Buffer("\x00" . $key->getPrivateKey()->getBinary());
        } else {
            $prefix = $scriptConfig->getPublicPrefix();
            $keyData = $key->getPublicKey()->getBuffer();
        }

        return new RawKeyParams(
            $prefix,
            $key->getDepth(),
            $key->getFingerprint(),
            $key->getSequence(),
            $key->getChainCode(),
            $keyData
        );
    }
}
