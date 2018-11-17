<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\KeyToScript;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2shP2wshScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2shScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2wshScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\KeyToScriptDataFactory;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\MultisigScriptDataFactory;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2pkhScriptDataFactory;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2wpkhScriptDataFactory;
use BitWasp\Bitcoin\Script\ScriptType;

class KeyToScriptHelper
{
    /**
     * @var ScriptDataFactory[]
     */
    private $cache = [];

    /**
     * @var PublicKeySerializerInterface
     */
    private $pubKeySer;

    /**
     * Slip132PrefixRegistry constructor.
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(EcAdapterInterface $ecAdapter)
    {
        $this->pubKeySer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
    }

    /**
     * @return P2pkhScriptDataFactory
     */
    public function getP2pkhFactory(): P2pkhScriptDataFactory
    {
        $key = ScriptType::P2PKH;
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = new P2pkhScriptDataFactory($this->pubKeySer);
        }
        return $this->cache[$key];
    }

    /**
     * @param int $numSignatures
     * @param int $numKeys
     * @param bool $sortCosignKeys
     * @return MultisigScriptDataFactory
     */
    public function getMultisigFactory(int $numSignatures, int $numKeys, bool $sortCosignKeys): MultisigScriptDataFactory
    {
        return new MultisigScriptDataFactory($numSignatures, $numKeys, $sortCosignKeys, $this->pubKeySer);
    }

    /**
     * @return P2wpkhScriptDataFactory
     */
    public function getP2wpkhFactory(): P2wpkhScriptDataFactory
    {
        $key = ScriptType::P2WKH;
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = new P2wpkhScriptDataFactory($this->pubKeySer);
        }
        return $this->cache[$key];
    }

    /**
     * @param KeyToScriptDataFactory $scriptFactory
     * @return ScriptDataFactory
     * @throws \BitWasp\Bitcoin\Exceptions\DisallowedScriptDataFactoryException
     */
    public function getP2shFactory(KeyToScriptDataFactory $scriptFactory): ScriptDataFactory
    {
        $key = sprintf("%s|%s", ScriptType::P2SH, $scriptFactory->getScriptType());
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = new P2shScriptDecorator($scriptFactory);
        }
        return $this->cache[$key];
    }

    /**
     * @param KeyToScriptDataFactory $scriptFactory
     * @return ScriptDataFactory
     * @throws \BitWasp\Bitcoin\Exceptions\DisallowedScriptDataFactoryException
     */
    public function getP2wshFactory(KeyToScriptDataFactory $scriptFactory): ScriptDataFactory
    {
        $key = sprintf("%s|%s", ScriptType::P2WSH, $scriptFactory->getScriptType());
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = new P2wshScriptDecorator($scriptFactory);
        }
        return $this->cache[$key];
    }

    /**
     * @param KeyToScriptDataFactory $scriptFactory
     * @return ScriptDataFactory
     * @throws \BitWasp\Bitcoin\Exceptions\DisallowedScriptDataFactoryException
     */
    public function getP2shP2wshFactory(KeyToScriptDataFactory $scriptFactory): ScriptDataFactory
    {
        $key = sprintf("%s|%s|%s", ScriptType::P2SH, ScriptType::P2WSH, $scriptFactory->getScriptType());
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = new P2shP2wshScriptDecorator($scriptFactory);
        }
        return $this->cache[$key];
    }
}
