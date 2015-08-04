<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key;


use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\Key;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Buffertools\Buffer;

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
        $this->ecAdapter = $ecAdapter;
        $this->pubkey_t = $secp256k1_pubkey_t;
        $this->compressed = $compressed;
    }

    /**
     * @return bool
     */
    public function isPrivate()
    {
        return false;
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
     * @return Buffer
     */
    public function getPubKeyHash()
    {
        return Hash::sha256ripe160($this->getBuffer());
    }

    /**
     * @return resource
     * @throws \Exception
     */
    private function clonePubkey()
    {
        $context = $this->ecAdapter->getContext();
        $serialized = '';
        if (1 !== secp256k1_ec_pubkey_serialize($context, $this->pubkey_t, $this->compressed, $serialized)) {
            throw new \Exception('');
        }

        $clone = '';
        if (1 !== secp256k1_ec_pubkey_parse($context, $serialized, $clone)) {
            throw new \Exception('');
        }
        return $clone;
    }

    /**
     * @param int $tweak
     * @return PublicKey
     * @throws \Exception
     */
    public function tweakAdd($tweak)
    {
        $context = $this->ecAdapter->getContext();
        $math = $this->ecAdapter->getMath();
        $bin = pack("H*", str_pad($math->decHex($tweak), 64, '0', STR_PAD_LEFT));

        $clone = $this->clonePubkey();
        if (1 !== secp256k1_ec_pubkey_tweak_add($context, $clone, $bin)){
            throw new \RuntimeException('Secp256k1: tweak add failed.');
        }

        return new PublicKey($this->ecAdapter, $clone, $this->compressed);
    }

    /**
     * @param int $tweak
     * @return PublicKey
     * @throws \Exception
     */
    public function tweakMul($tweak)
    {
        $context = $this->ecAdapter->getContext();
        $math = $this->ecAdapter->getMath();
        $bin = pack("H*", str_pad($math->decHex($tweak), 64, '0', STR_PAD_LEFT));

        $clone = $this->clonePubkey();
        if (1 !== secp256k1_ec_pubkey_tweak_mul($context, $clone, $bin)){
            throw new \RuntimeException('Secp256k1: tweak mul failed.');
        }

        return new PublicKey($this->ecAdapter, $clone, $this->compressed);
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        $out = '';
        secp256k1_ec_pubkey_serialize($this->ecAdapter->getContext(), $this->pubkey_t, $this->compressed, $out);
        return new Buffer($out);
    }
}