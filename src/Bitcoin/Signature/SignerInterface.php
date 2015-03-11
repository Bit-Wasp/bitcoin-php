<?php

namespace Afk11\Bitcoin\Signature;

use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Crypto\Random\RbgInterface;
use Afk11\Bitcoin\Key\PrivateKeyInterface;
use Afk11\Bitcoin\Key\PublicKeyInterface;

interface SignerInterface
{
    public function sign(PrivateKeyInterface $privateKey, Buffer $buffer, RbgInterface $nonce);
    public function verify(PublicKeyInterface $publicKey, Buffer $buffer, SignatureInterface $signature);
}
