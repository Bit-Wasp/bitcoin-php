<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature;

interface SchnorrSignatureInterface
{
    public function getR(): \GMP;
    public function getS(): \GMP;
}
