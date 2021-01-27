<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter\Impl\Secp256k1\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key\PublicKey;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class PublicKeyTest extends AbstractTestCase
{
    private function callConstructor($value)
    {
        return new PublicKey(EcAdapterFactory::getSecp256k1(new Math(), Bitcoin::getGenerator()), $value, true);
    }
    public function testNotResource()
    {
        if (!function_exists('secp256k1_context_create')) {
            $this->markTestSkipped("secp256k1 not installed");
        }
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Secp256k1\Key\PublicKey expects ' . SECP256K1_TYPE_PUBKEY . ' resource');
        $this->callConstructor("");
    }
    public function testWrongResourceType()
    {
        if (!function_exists('secp256k1_context_create')) {
            $this->markTestSkipped("secp256k1 not installed");
        }
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Secp256k1\Key\PublicKey expects ' . SECP256K1_TYPE_PUBKEY . ' resource');
        $this->callConstructor(gmp_init(1));
    }
}
