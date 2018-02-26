<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\Deterministic\HdPrefix;

use BitWasp\Bitcoin\Network\NetworkInterface;

class GlobalPrefixConfig
{
    /**
     * @var NetworkConfig[]
     */
    private $networkConfigs = [];

    /**
     * ScriptPrefixConfig constructor.
     * @param NetworkConfig[] $config
     */
    public function __construct(array $config)
    {
        foreach ($config as $networkPrefixConfig) {
            if (!($networkPrefixConfig instanceof NetworkConfig)) {
                throw new \InvalidArgumentException("expecting array of NetworkPrefixConfig");
            }

            $networkClass = get_class($networkPrefixConfig->getNetwork());
            if (array_key_exists($networkClass, $this->networkConfigs)) {
                throw new \InvalidArgumentException("multiple configs for network");
            }

            $this->networkConfigs[$networkClass] = $networkPrefixConfig;
        }
    }

    /**
     * @param NetworkInterface $network
     * @return NetworkConfig
     */
    public function getNetworkConfig(NetworkInterface $network): NetworkConfig
    {
        $class = get_class($network);
        if (!array_key_exists($class, $this->networkConfigs)) {
            throw new \InvalidArgumentException("Network not registered with GlobalHdPrefixConfig");
        }

        return $this->networkConfigs[$class];
    }
}
