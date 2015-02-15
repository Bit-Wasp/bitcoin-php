<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 17/01/15
 * Time: 04:39
 */

namespace Afk11\Bitcoin\Signature;

use Bitcoin\Buffer;
use Afk11\Bitcoin\Crypto\Random\RbgInterface;
use Afk11\Bitcoin\Key\PrivateKeyInterface;
use Afk11\Bitcoin\Key\PublicKeyInterface;
use Afk11\Bitcoin\Signature\K\KInterface;

interface SignerInterface
{
    public function sign(PrivateKeyInterface $privateKey, Buffer $buffer, RbgInterface $nonce);
    public function verify(PublicKeyInterface $publicKey, Buffer $buffer, SignatureInterface $signature);
}
