<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SchnorrSignatureInterface;
use BitWasp\Bitcoin\SerializableInterface;
use BitWasp\Buffertools\BufferInterface;

interface XOnlyPublicKeyInterface extends SerializableInterface
{
    public function hasSquareY(): bool;
    public function verifySchnorr(BufferInterface $msg32, SchnorrSignatureInterface $schnorrSig): bool;
    public function tweakAdd(BufferInterface $tweak32): XOnlyPublicKeyInterface;
    public function checkPayToContract(XOnlyPublicKeyInterface $base, BufferInterface $hash, bool $negated): bool;
    public function getBuffer(): BufferInterface;
}
