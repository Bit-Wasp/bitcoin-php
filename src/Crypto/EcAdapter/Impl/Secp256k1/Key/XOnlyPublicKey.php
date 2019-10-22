<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key;


use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature\SchnorrSignature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\XOnlyPublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SchnorrSignatureInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class XOnlyPublicKey implements XOnlyPublicKeyInterface
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
    private $isPositive;

    /**
     * @param resource $context
     * @param resource $xonlyKey
     */
    public function __construct($context, $xonlyKey)
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
    }

    public function isPositive(): bool
    {
        if (null === $this->isPositive) {
            $x = gmp_init(unpack("H*", $this->getBuffer()->getBinary())[1], 16);
            $p = gmp_init("FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F", 16);
            // todo: is this === 1 or >= 0
            // https://github.com/bitcoin-core/secp256k1/blob/1c131affd3c3402f269b56685bca63c631cfcf26/src/field_impl.h#L311
            // https://github.com/sipa/bitcoin/commit/348b0e0e00c0ebe57c180e49b08edcecde5f9158#diff-607598e1a39100b1883191275b789557R278
            $this->isPositive = gmp_jacobi($x, $p) === 1;
        }
        return $this->isPositive;
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
        if (!secp256k1_xonly_pubkey_tweak_add($this->context, $tweaked, $pubkey, $tweak32->getBinary())) {
            throw new \RuntimeException("failed to tweak pubkey");
        }
        return new XOnlyPublicKey($this->context, $pubkey);
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