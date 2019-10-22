<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature;

use BitWasp\Bitcoin\Serializable;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class SchnorrSignature extends Serializable implements SchnorrSignatureInterface
{
    private $context;
    private $schnorrSig;
    public function __construct($context, $schnorrSig)
    {
        $this->context = $context;
        $this->schnorrSig = $schnorrSig;
    }
    public function getResource()
    {
        return $this->schnorrSig;
    }
    public function getBuffer(): BufferInterface
    {
        $out = '';
        if (!secp256k1_schnorrsig_serialize($this->context, $out, $this->schnorrSig)) {
            throw new \RuntimeException("failed to serialize schnorrsig");
        }
        return new Buffer($out);
    }
}