<?php
/**
 * Created by PhpStorm.
 * User: tk
 * Date: 28/12/15
 * Time: 14:54
 */
namespace BitWasp\Bitcoin\Signature;

use BitWasp\Buffertools\Buffer;

interface SignatureSortInterface
{
    /**
     * @param \BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface[] $signatures
     * @param \BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface[] $publicKeys
     * @param Buffer $messageHash
     * @return \SplObjectStorage
     */
    public function link(array $signatures, array $publicKeys, Buffer $messageHash);
}
