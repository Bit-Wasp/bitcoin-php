<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\Deterministic\Slip132;

use BitWasp\Bitcoin\Key\Deterministic\Slip132\PrefixRegistry;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class PrefixRegistryTest extends AbstractTestCase
{
    public function testMaps()
    {
        $key = 'abc';
        $pub = 'abcd1234';
        $priv = 'abcd1234';

        $registry = new PrefixRegistry([
            $key => [$priv, $pub]
        ]);

        $res = $registry->getPrefixes($key);
        $this->assertInternalType('array', $res);
        $this->assertCount(2, $res);
        $this->assertEquals($priv, $res[0]);
        $this->assertEquals($pub, $res[1]);
    }

    public function testUnknown()
    {
        $registry = new PrefixRegistry([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown script type");

        $registry->getPrefixes('abc');
    }

    public function testInvalidArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Expecting script type as key");

        new PrefixRegistry([
            ''
        ]);
    }

    public function testInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Expecting two BIP32 prefixes");

        new PrefixRegistry([
            ScriptType::P2WKH => ['', '', ''],
        ]);
    }

    public function testInvalidPub()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid public prefix");

        new PrefixRegistry([
            ScriptType::P2WKH => ['aaaaaaaa', ''],
        ]);
    }

    public function testInvalidPriv()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid private prefix");

        new PrefixRegistry([
            ScriptType::P2WKH => ['', 'aaaaaaaa'],
        ]);
    }
}
