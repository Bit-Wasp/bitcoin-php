<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 17/01/15
 * Time: 04:39
 */

namespace Bitcoin\Signature;

use Bitcoin\Buffer;
use Afk11\Bitcoin\Crypto\Random\RbgInterface;
use Bitcoin\Key\PrivateKeyInterface;
use Bitcoin\Key\PublicKeyInterface;
use Bitcoin\Signature\K\KInterface;

interface SignerInterface
{
    public function sign(PrivateKeyInterface $privateKey, Buffer $buffer, RbgInterface $nonce);
    public function verify(PublicKeyInterface $publicKey, Buffer $buffer, SignatureInterface $signature);
}
