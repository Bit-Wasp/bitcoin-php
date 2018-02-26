<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\Deterministic\HdPrefix;

use BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\ScriptPrefix;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2wpkhScriptDataFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class ScriptPrefixTest extends AbstractTestCase
{
    public function testScriptPrefix()
    {
        $factory = new P2wpkhScriptDataFactory();
        $pubPrefix = "04b24746";
        $privPrefix = "04b2430c";
        $prefix = new ScriptPrefix($factory, $privPrefix, $pubPrefix);
        $this->assertEquals($pubPrefix, $prefix->getPublicPrefix());
        $this->assertEquals($privPrefix, $prefix->getPrivatePrefix());
        $this->assertEquals($factory, $prefix->getScriptDataFactory());
    }

    public function testBadLengthPrivatePrefix()
    {
        $factory = new P2wpkhScriptDataFactory();
        $pubPrefix = "04b24746";
        $privPrefix = "dadd0c";
        $this->expectException(InvalidNetworkParameter::class);
        $this->expectExceptionMessage("Invalid HD private prefix: wrong length");

        new ScriptPrefix($factory, $privPrefix, $pubPrefix);
    }

    public function testBadHexPrivatePrefix()
    {
        $factory = new P2wpkhScriptDataFactory();
        $pubPrefix = "04b24746";
        $privPrefix = "dadgad0c";
        $this->expectException(InvalidNetworkParameter::class);
        $this->expectExceptionMessage("Invalid HD private prefix: expecting hex");

        new ScriptPrefix($factory, $privPrefix, $pubPrefix);
    }

    public function testBadLengthPublicPrefix()
    {
        $factory = new P2wpkhScriptDataFactory();
        $privPrefix = "04b24746";
        $pubPrefix = "dadd0c";
        $this->expectException(InvalidNetworkParameter::class);
        $this->expectExceptionMessage("Invalid HD public prefix: wrong length");

        new ScriptPrefix($factory, $privPrefix, $pubPrefix);
    }

    public function testBadHexPublicPrefix()
    {
        $factory = new P2wpkhScriptDataFactory();
        $privPrefix = "04b24746";
        $pubPrefix = "dadgad0c";
        $this->expectException(InvalidNetworkParameter::class);
        $this->expectExceptionMessage("Invalid HD public prefix: expecting hex");

        new ScriptPrefix($factory, $privPrefix, $pubPrefix);
    }
}
