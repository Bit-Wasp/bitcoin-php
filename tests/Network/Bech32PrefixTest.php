<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Network;

use BitWasp\Bitcoin\Exceptions\MissingBech32Prefix;
use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Network\Networks\Bitcoin;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class Bech32PrefixTest extends AbstractTestCase
{
    public function testHasKnownBech32Byte()
    {
        $method = new \ReflectionMethod(Bitcoin::class, 'hasBech32Prefix');
        $method->setAccessible(true);
        $hasPrefix = $method->invoke(new Bitcoin(), Network::BECH32_PREFIX_SEGWIT);
        $this->assertTrue($hasPrefix);
    }

    public function testHasUnknownBech32Byte()
    {
        $method = new \ReflectionMethod(Bitcoin::class, 'hasBech32Prefix');
        $method->setAccessible(true);
        $hasPrefix = $method->invoke(new Bitcoin(), "don't know this one");
        $this->assertFalse($hasPrefix);
    }

    public function testGetKnownBech32Byte()
    {
        $method = new \ReflectionMethod(Bitcoin::class, 'getBech32Prefix');
        $method->setAccessible(true);
        $prefix = $method->invoke(new Bitcoin(), Network::BECH32_PREFIX_SEGWIT);
        $this->assertSame('bc', $prefix);
    }

    public function testGetUnknownBech32Byte()
    {
        $method = new \ReflectionMethod(Bitcoin::class, 'getBech32Prefix');
        $method->setAccessible(true);

        $this->expectException(MissingBech32Prefix::class);

        $method->invoke(new Bitcoin(), "unknown!");
    }

    public function testGetBech32TypeByte()
    {
        $network = new Bitcoin();
        $method = new \ReflectionProperty(Bitcoin::class, 'bech32PrefixMap');
        $method->setAccessible(true);

        $map = $value = $method->getValue($network);
        $this->assertEquals($map[Network::BECH32_PREFIX_SEGWIT], $network->getSegwitBech32Prefix());
    }
}
