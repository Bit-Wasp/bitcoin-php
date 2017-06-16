<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Serializer\Key\PublicKeySerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\Key;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class PublicKey extends Key implements PublicKeyInterface
{
    /**
     * @var EcAdapter
     */
    private $ecAdapter;

    /**
     * @var bool|false
     */
    private $compressed;

    /**
     * @var resource
     */
    private $pubkey_t;

    /**
     * @param EcAdapter $ecAdapter
     * @param resource $secp256k1_pubkey_t
     * @param bool|false $compressed
     */
    public function __construct(EcAdapter $ecAdapter, $secp256k1_pubkey_t, $compressed = false)
    {
        if (!is_resource($secp256k1_pubkey_t) ||
            !get_resource_type($secp256k1_pubkey_t) === SECP256K1_TYPE_PUBKEY) {
            throw new \InvalidArgumentException('Secp256k1\Key\PublicKey expects ' . SECP256K1_TYPE_PUBKEY . ' resource');
        }

        if (false === is_bool($compressed)) {
            throw new \InvalidArgumentException('PublicKey: Compressed must be a boolean');
        }

        $this->ecAdapter = $ecAdapter;
        $this->pubkey_t = $secp256k1_pubkey_t;
        $this->compressed = $compressed;
    }
    
    /**
     * @param BufferInterface $msg32
     * @param SignatureInterface $signature
     * @return bool
     */
    public function verify(BufferInterface $msg32, SignatureInterface $signature)
    {
        return $this->ecAdapter->verify($msg32, $this, $signature);
    }

    /**
     * @param PublicKey $other
     * @return bool
     */
    private function doEquals(PublicKey $other)
    {
        $context = $this->ecAdapter->getContext();
        $pubA = '';
        $pubB = '';
        if (!(secp256k1_ec_pubkey_serialize($context, $pubA, $this->pubkey_t, $this->compressed) && secp256k1_ec_pubkey_serialize($context, $pubB, $other->pubkey_t, $this->compressed))) {
            throw new \RuntimeException('Unable to serialize public key during equals');
        }

        return hash_equals($pubA, $pubB);
    }

    /**
     * @param PublicKeyInterface $other
     * @return bool
     */
    public function equals(PublicKeyInterface $other)
    {
        /** @var PublicKey $other */
        return $this->doEquals($other);
    }

    /**
     * @return bool|false
     */
    public function isCompressed()
    {
        return $this->compressed;
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->pubkey_t;
    }

    /**
     * @return resource
     * @throws \Exception
     */
    private function clonePubkey()
    {
        $context = $this->ecAdapter->getContext();
        $serialized = '';
        if (1 !== secp256k1_ec_pubkey_serialize($context, $serialized, $this->pubkey_t, $this->compressed)) {
            throw new \Exception('Secp256k1: pubkey serialize');
        }

        /** @var resource $clone */
        $clone = '';
        if (1 !== secp256k1_ec_pubkey_parse($context, $clone, $serialized)) {
            throw new \Exception('Secp256k1 pubkey parse');
        }

        return $clone;
    }

    /**
     * @param \GMP $tweak
     * @return PublicKey
     * @throws \Exception
     */
    public function tweakAdd(\GMP $tweak)
    {
        $context = $this->ecAdapter->getContext();
        $bin = Buffer::int(gmp_strval($tweak, 10), 32)->getBinary();

        $clone = $this->clonePubkey();
        if (1 !== secp256k1_ec_pubkey_tweak_add($context, $clone, $bin)) {
            throw new \RuntimeException('Secp256k1: tweak add failed.');
        }

        return new PublicKey($this->ecAdapter, $clone, $this->compressed);
    }

    /**
     * @param \GMP $tweak
     * @return PublicKey
     * @throws \Exception
     */
    public function tweakMul(\GMP $tweak)
    {
        $context = $this->ecAdapter->getContext();
        $bin = Buffer::int(gmp_strval($tweak, 10), 32)->getBinary();

        $clone = $this->clonePubkey();
        if (1 !== secp256k1_ec_pubkey_tweak_mul($context, $clone, $bin)) {
            throw new \RuntimeException('Secp256k1: tweak mul failed.');
        }

        return new PublicKey($this->ecAdapter, $clone, $this->compressed);
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer()
    {
        return (new PublicKeySerializer($this->ecAdapter))->serialize($this);
    }
}
