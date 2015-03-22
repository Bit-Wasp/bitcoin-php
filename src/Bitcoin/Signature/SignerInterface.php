<?php

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\PublicKeyInterface;

interface SignerInterface
{
    public function sign(PrivateKeyInterface $privateKey, Buffer $buffer, RbgInterface $nonce);
    public function verify(PublicKeyInterface $publicKey, Buffer $buffer, SignatureInterface $signature);
}
