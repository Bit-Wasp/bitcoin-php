<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature\SchnorrSignature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\XOnlyPublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SchnorrSignatureInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class XOnlyPublicKey extends Serializable implements XOnlyPublicKeyInterface
{
    /**
     * @var resource
     */
    private $context;

    /**
     * @var resource
     */
    private $xonlyKey;

    /**
     * @var bool
     */
    private $hasSquareY;

    /**
     * @param resource $context
     * @param resource $xonlyKey
     * @param bool $hasSquareY
     */
    public function __construct($context, $xonlyKey, bool $hasSquareY)
    {
        if (!is_resource($context) ||
            !get_resource_type($context) === SECP256K1_TYPE_CONTEXT) {
            throw new \InvalidArgumentException('Secp256k1\Key\XOnlyPublicKey expects ' . SECP256K1_TYPE_CONTEXT . ' resource');
        }

        if (!(is_resource($xonlyKey) && get_resource_type($xonlyKey) === SECP256K1_TYPE_XONLY_PUBKEY)) {
            throw new \InvalidArgumentException('Secp256k1\Key\XOnlyPublicKey expects ' . SECP256K1_TYPE_XONLY_PUBKEY . ' resource');
        }

        $this->context = $context;
        $this->xonlyKey = $xonlyKey;
        $this->hasSquareY = $hasSquareY;
    }

    public function hasSquareY(): bool
    {
        return $this->hasSquareY;
    }

    private function doVerifySchnorr(BufferInterface $msg32, SchnorrSignature $schnorrSig): bool
    {
        return (bool) secp256k1_schnorrsig_verify($this->context, $schnorrSig->getResource(), $msg32->getBinary(), $this->xonlyKey);
    }

    public function verifySchnorr(BufferInterface $msg32, SchnorrSignatureInterface $schnorrSignature): bool
    {
        /** @var SchnorrSignature $schnorrSignature */
        return $this->doVerifySchnorr($msg32, $schnorrSignature);
    }
    /**
     * @return resource
     * @throws \Exception
     */
    private function clonePubkey()
    {
        $context = $this->context;
        $serialized = '';
        if (1 !== secp256k1_xonly_pubkey_serialize($context, $serialized, $this->xonlyKey)) {
            throw new \Exception('failed to serialize xonly pubkey for clone');
        }

        /** @var resource $clone */
        $clone = null;
        if (1 !== secp256k1_xonly_pubkey_parse($context, $clone, $serialized)) {
            throw new \Exception('failed to parse xonly pubkey');
        }

        return $clone;
    }

    public function tweakAdd(BufferInterface $tweak32): XOnlyPublicKeyInterface
    {
        $pubkey = $this->clonePubkey();
        $tweaked = null;
        $hasSquareY = null;
        if (!secp256k1_xonly_pubkey_tweak_add($this->context, $tweaked, $hasSquareY, $pubkey, $tweak32->getBinary())) {
            throw new \RuntimeException("failed to tweak pubkey");
        }
        return new XOnlyPublicKey($this->context, $tweaked, (bool) $hasSquareY);
    }

    public function getBuffer(): BufferInterface
    {
        $out = '';
        if (!secp256k1_xonly_pubkey_serialize($this->context, $out, $this->xonlyKey)) {
            throw new \RuntimeException("failed to serialize xonly pubkey!");
        }
        return new Buffer($out);
    }
}
