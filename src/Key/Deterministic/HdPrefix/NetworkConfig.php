<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\Deterministic\HdPrefix;

use BitWasp\Bitcoin\Network\NetworkInterface;

class NetworkConfig
{
    /**
     * @var NetworkInterface
     */
    private $network;

    /**
     * @var ScriptPrefix[]
     */
    private $scriptPrefixMap = [];

    /**
     * @var ScriptPrefix[]
     */
    private $scriptTypeMap = [];

    /**
     * NetworkHdKeyPrefixConfig constructor.
     * @param NetworkInterface $network
     * @param ScriptPrefix[] $prefixConfigList
     */
    public function __construct(NetworkInterface $network, array $prefixConfigList)
    {
        foreach ($prefixConfigList as $config) {
            if (!($config instanceof ScriptPrefix)) {
                throw new \InvalidArgumentException("expecting array of NetworkPrefixConfig");
            }
            $this->setupConfig($config);
        }

        $this->network = $network;
    }

    /**
     * @param ScriptPrefix $config
     */
    private function setupConfig(ScriptPrefix $config)
    {
        $this->checkForOverwriting($config);

        $this->scriptPrefixMap[$config->getPrivatePrefix()] = $config;
        $this->scriptPrefixMap[$config->getPublicPrefix()] = $config;
        $this->scriptTypeMap[$config->getScriptDataFactory()->getScriptType()] = $config;
    }

    /**
     * @param ScriptPrefix $config
     */
    private function checkForOverwriting(ScriptPrefix $config)
    {
        if (array_key_exists($config->getPublicPrefix(), $this->scriptPrefixMap)) {
            $this->rejectConflictPrefix($config, $config->getPublicPrefix());
        }

        if (array_key_exists($config->getPrivatePrefix(), $this->scriptPrefixMap)) {
            $this->rejectConflictPrefix($config, $config->getPrivatePrefix());
        }

        if (array_key_exists($config->getScriptDataFactory()->getScriptType(), $this->scriptTypeMap)) {
            $this->rejectConflictScriptType($config);
        }
    }

    /**
     * @param ScriptPrefix $config
     * @param string $prefix
     */
    private function rejectConflictPrefix(ScriptPrefix $config, $prefix)
    {
        $conflict = $this->scriptPrefixMap[$prefix];
        throw new \RuntimeException(sprintf(
            "A BIP32 prefix for %s conflicts with the %s BIP32 prefix of %s",
            $config->getScriptDataFactory()->getScriptType(),
            $prefix === $config->getPublicPrefix() ? "public" : "private",
            $conflict->getScriptDataFactory()->getScriptType()
        ));
    }

    /**
     * @param ScriptPrefix $config
     */
    private function rejectConflictScriptType(ScriptPrefix $config)
    {
        throw new \RuntimeException(sprintf(
            "The script type %s has a conflict",
            $config->getScriptDataFactory()->getScriptType()
        ));
    }

    /**
     * @return NetworkInterface
     */
    public function getNetwork()
    {
        return $this->network;
    }

    /**
     * @param string $prefix
     * @return ScriptPrefix
     */
    public function getConfigForPrefix($prefix)
    {
        if (!array_key_exists($prefix, $this->scriptPrefixMap)) {
            throw new \InvalidArgumentException("Prefix not configured for network");
        }

        return $this->scriptPrefixMap[$prefix];
    }

    /**
     * @param string $scriptType
     * @return ScriptPrefix
     */
    public function getConfigForScriptType($scriptType)
    {
        if (!array_key_exists($scriptType, $this->scriptTypeMap)) {
            throw new \InvalidArgumentException("Script type not configured for network");
        }

        return $this->scriptTypeMap[$scriptType];
    }
}
