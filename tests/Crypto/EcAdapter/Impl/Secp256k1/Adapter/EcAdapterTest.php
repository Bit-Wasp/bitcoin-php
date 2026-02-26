<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter\Impl\Secp256k1\Adapter;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class EcAdapterTest extends AbstractTestCase
{
    private function callConstructor($value)
    {
        $math = new Math();
        $G = Bitcoin::getGenerator();
        return new EcAdapter($math, $G, $value);
    }
    public function testContextNotResource()
    {
        if (!function_exists('secp256k1_context_create')) {
            $this->markTestSkipped("secp256k1 not installed");
        }
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Secp256k1: Must pass a secp256k1_context_t resource");
        $this->callConstructor("");
    }
    public function testContextWrongResource()
    {
        if (!function_exists('secp256k1_context_create')) {
            $this->markTestSkipped("secp256k1 not installed");
        }
        $ctx = gmp_init(1);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Secp256k1: Must pass a secp256k1_context_t resource");
        $this->callConstructor($ctx);
    }
}
