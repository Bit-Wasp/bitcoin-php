<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\Deterministic\HdPrefix;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\GlobalPrefixConfig;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\NetworkConfig;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class GlobalPrefixConfigTest extends AbstractTestCase
{
    public function testInvalidArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("expecting array of NetworkPrefixConfig");

        new GlobalPrefixConfig([
            Bitcoin::getNetwork()
        ]);
    }

    public function testDuplicateNetworksNotAllowed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("multiple configs for network");

        new GlobalPrefixConfig([
            new NetworkConfig(NetworkFactory::bitcoin(), []),
            new NetworkConfig(NetworkFactory::bitcoin(), []),
        ]);
    }

    public function testMultipleNetworksWorks()
    {
        $btc = NetworkFactory::bitcoin();
        $btcConfig = new NetworkConfig($btc, []);
        $tbtc = NetworkFactory::bitcoinTestnet();
        $tbtcConfig = new NetworkConfig($tbtc, []);
        $config = new GlobalPrefixConfig([
            $btcConfig,
            $tbtcConfig,
        ]);

        $this->assertSame($btcConfig, $config->getNetworkConfig($btc));
        $this->assertSame($tbtcConfig, $config->getNetworkConfig($tbtc));
    }

    public function testUnknownNetwork()
    {
        $btc = NetworkFactory::bitcoin();
        $btcConfig = new NetworkConfig($btc, []);
        $tbtc = NetworkFactory::bitcoinTestnet();
        $config = new GlobalPrefixConfig([
            $btcConfig,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Network not registered with GlobalHdPrefixConfig");

        $config->getNetworkConfig($tbtc);
    }
}
