<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Signature;

use BitWasp\Bitcoin\SerializableInterface;

interface SignatureInterface extends SerializableInterface, \Mdanter\Ecc\Crypto\Signature\SignatureInterface
{
    /**
     * @return bool
     */
    public function equals(SignatureInterface $signature);
}
