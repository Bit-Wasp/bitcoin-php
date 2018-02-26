<?php

namespace BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey;

use BitWasp\Bitcoin\Network\NetworkInterface;

class GlobalHdKeyPrefixConfig
{
    /**
     * @var NetworkHdKeyPrefixConfig[]
     */
    private $networkConfigs = [];

    /**
     * ScriptPrefixConfig constructor.
     * @param NetworkHdKeyPrefixConfig[] $config
     */
    public function __construct(array $config)
    {
        foreach ($config as $networkPrefixConfig) {
            $networkClass = get_class($networkPrefixConfig->getNetwork());
            if (!array_key_exists($networkClass, $this->networkConfigs)) {
                $this->networkConfigs[$networkClass] = [];
            }

            $this->networkConfigs[$networkClass] = $networkPrefixConfig;
        }
    }

    /**
     * @param NetworkInterface $network
     * @return NetworkHdKeyPrefixConfig
     */
    public function getNetworkHdPrefixConfig(NetworkInterface $network)
    {
        $class = get_class($network);
        if (!array_key_exists($class, $this->networkConfigs)) {
            throw new \InvalidArgumentException("Network not registered with GlobalHdPrefixConfig");
        }

        return $this->networkConfigs[$class];
    }
}
