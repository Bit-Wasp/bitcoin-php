<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature;

interface SchnorrSignatureInterface extends \BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SchnorrSignatureInterface
{
    /**
     * @return resource
     */
    public function getResource();
}