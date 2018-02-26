<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\Deterministic\HdPrefix;

use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\NetworkConfig;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\ScriptPrefix;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2pkhScriptDataFactory;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2wpkhScriptDataFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class NetworkConfigTest extends AbstractTestCase
{
    public function testGetNetwork()
    {
        $network = NetworkFactory::bitcoin();
        $config = new NetworkConfig($network, []);
        $this->assertEquals($network, $config->getNetwork());
    }

    public function testGetConfigForUnknownScriptType()
    {
        $network = NetworkFactory::bitcoin();
        $config = new NetworkConfig($network, []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Script type not configured for network");

        $config->getConfigForScriptType(ScriptType::P2WKH);
    }

    public function testGetConfigByScriptType()
    {
        $pubPrefix = "04b24746";
        $privPrefix = "04b2430c";
        $factory = new P2wpkhScriptDataFactory();
        $prefix = new ScriptPrefix($factory, $privPrefix, $pubPrefix);

        $network = NetworkFactory::bitcoin();
        $config = new NetworkConfig($network, [$prefix]);

        $prefixConfig = $config->getConfigForScriptType($factory->getScriptType());
        $this->assertEquals($prefixConfig, $prefix);
    }

    public function testGetConfigByPrefix()
    {
        $factory = new P2wpkhScriptDataFactory();
        $prefix = new ScriptPrefix($factory, "04b2430c", "04b24746");

        $network = NetworkFactory::bitcoin();
        $config = new NetworkConfig($network, [$prefix]);

        $prefixConfig = $config->getConfigForPrefix($prefix->getPrivatePrefix());
        $this->assertEquals($prefixConfig, $prefix);

        $prefixConfig = $config->getConfigForPrefix($prefix->getPublicPrefix());
        $this->assertEquals($prefixConfig, $prefix);
    }

    public function testGetConfigForUnknownPrefix()
    {
        $network = NetworkFactory::bitcoin();
        $config = new NetworkConfig($network, []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Prefix not configured for network");

        $config->getConfigForPrefix("abababab");
    }

    public function testInvalidArrayIsRejected()
    {
        $network = NetworkFactory::bitcoin();

        $pubPrefix = "04b24746";
        $privPrefix = "04b2430c";
        $factory = new P2wpkhScriptDataFactory();
        $prefix = new ScriptPrefix($factory, $privPrefix, $pubPrefix);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("expecting array of NetworkPrefixConfig");

        new NetworkConfig($network, [
            $prefix,
            $network
        ]);
    }

    public function testCheckForPublicPrefixOverwriting()
    {
        $network = NetworkFactory::bitcoin();

        $prefix1 = new ScriptPrefix(new P2wpkhScriptDataFactory(), "aaaaaaaa", "bbbbbbbb");
        $prefix2 = new ScriptPrefix(new P2pkhScriptDataFactory(), "abababab", "bbbbbbbb");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("A BIP32 prefix for pubkeyhash conflicts with the public BIP32 prefix of witness_v0_keyhash");

        new NetworkConfig($network, [
            $prefix1,
            $prefix2,
        ]);
    }

    public function testCheckForPrivatePrefixOverwriting()
    {
        $network = NetworkFactory::bitcoin();

        $prefix1 = new ScriptPrefix(new P2wpkhScriptDataFactory(), "aaaaaaaa", "ffffbbbb");
        $prefix2 = new ScriptPrefix(new P2pkhScriptDataFactory(), "aaaaaaaa", "ddddbbbb");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("A BIP32 prefix for pubkeyhash conflicts with the private BIP32 prefix of witness_v0_keyhash");

        new NetworkConfig($network, [
            $prefix1,
            $prefix2,
        ]);
    }

    public function testCheckForScriptTypeOverwriting()
    {
        $network = NetworkFactory::bitcoin();

        $prefix1 = new ScriptPrefix(new P2pkhScriptDataFactory(), "abcdef12", "34567890");
        $prefix2 = new ScriptPrefix(new P2pkhScriptDataFactory(), "aaaaaaaa", "bbbbbbbb");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("The script type pubkeyhash has a conflict");

        new NetworkConfig($network, [
            $prefix1,
            $prefix2,
        ]);
    }
}
