<?php

namespace BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey;

use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Network\NetworkInterface;

class NetworkHdKeyPrefixConfig
{
    /**
     * @var Network
     */
    private $network;

    /**
     * @var NetworkScriptPrefix[]
     */
    private $scriptPrefixMap = [];

    /**
     * @var NetworkScriptPrefix[]
     */
    private $scriptTypeMap = [];

    /**
     * NetworkHdKeyPrefixConfig constructor.
     * @param NetworkInterface $network
     * @param NetworkScriptPrefix[] $prefixConfigList
     */
    public function __construct(NetworkInterface $network, array $prefixConfigList)
    {
        foreach ($prefixConfigList as $config) {
            if (!($config instanceof NetworkScriptPrefix)) {
                throw new \InvalidArgumentException();
            }
            $this->setupConfig($config);
        }

        $this->network = $network;
    }

    /**
     * @param NetworkScriptPrefix $config
     */
    private function setupConfig(NetworkScriptPrefix $config)
    {
        $this->checkForOverwriting($config);

        $this->scriptPrefixMap[$config->getPrivatePrefix()] = $config;
        $this->scriptPrefixMap[$config->getPublicPrefix()] = $config;
        $this->scriptTypeMap[$config->getScriptDataFactory()->getScriptType()] = $config;
        echo "registered {$config->getScriptDataFactory()->getScriptType()}\n";
    }

    /**
     * @param NetworkScriptPrefix $config
     */
    private function checkForOverwriting(NetworkScriptPrefix $config)
    {
        if (array_key_exists($config->getPublicPrefix(), $this->scriptPrefixMap)) {
            $this->rejectConflictPrefix($config, $config->getPublicPrefix());
        }

        if (array_key_exists($config->getPrivatePrefix(), $this->scriptPrefixMap)) {
            $this->rejectConflictPrefix($config, $config->getPrivatePrefix());
        }
    }

    /**
     * @param NetworkScriptPrefix $config
     * @param string $prefix
     */
    private function rejectConflictPrefix(NetworkScriptPrefix $config, $prefix)
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
     * @return Network
     */
    public function getNetwork()
    {
        return $this->network;
    }

    /**
     * @return NetworkScriptPrefix[]
     */
    public function getPrefixList()
    {
        return $this->scriptPrefixMap;
    }

    /**
     * @param string $prefix
     * @return NetworkScriptPrefix
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
     * @return NetworkScriptPrefix
     */
    public function getConfigForScriptType($scriptType)
    {
        if (!array_key_exists($scriptType, $this->scriptTypeMap)) {
            throw new \InvalidArgumentException("Script type not configured for network");
        }

        return $this->scriptTypeMap[$scriptType];
    }
}
