<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter\Impl\Secp256k1\Signature;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature\CompactSignature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature\Signature;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class CompactSignatureTest extends AbstractTestCase
{
    private function callConstructor($value)
    {
        return new CompactSignature(EcAdapterFactory::getSecp256k1(new Math(), Bitcoin::getGenerator()), $value, 1, true);
    }
    public function testNotResource()
    {
        if (!function_exists('secp256k1_context_create')) {
            $this->markTestSkipped("secp256k1 not installed");
        }
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CompactSignature: must pass recoverable signature resource');
        $this->callConstructor("");
    }
    public function testWrongResourceType()
    {
        if (!function_exists('secp256k1_context_create')) {
            $this->markTestSkipped("secp256k1 not installed");
        }
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CompactSignature: must pass recoverable signature resource');
        $this->callConstructor(gmp_init(1));
    }
}
