<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Bitcoin\SerializableInterface;

interface TransactionSignatureInterface extends SerializableInterface
{
    /**
     * @return SignatureInterface
     */
    public function getSignature(): SignatureInterface;

    /**
     * @return int
     */
    public function getHashType(): int;

    /**
     * @param TransactionSignatureInterface $other
     * @return bool
     */
    public function equals(TransactionSignatureInterface $other): bool;
}
