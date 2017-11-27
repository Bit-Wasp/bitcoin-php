<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature;

interface SignatureInterface extends \BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface
{
    /**
     * @return resource
     */
    public function getResource();
}
