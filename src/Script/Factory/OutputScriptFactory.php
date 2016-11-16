<?php

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;

class OutputScriptFactory
{
    /**
     * @param AddressInterface $address
     * @return ScriptInterface
     */
    public function payToAddress(AddressInterface $address)
    {
        return $address instanceof ScriptHashAddress
            ? ScriptFactory::sequence([Opcodes::OP_HASH160, $address->getHash(), Opcodes::OP_EQUAL])
            : ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $address->getHash(), Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]);
    }

    /**
     * Create a Pay to pubkey output
     *
     * @param PublicKeyInterface  $publicKey
     * @return ScriptInterface
     */
    public function payToPubKey(PublicKeyInterface $publicKey)
    {
        return ScriptFactory::sequence([$publicKey->getBuffer(), Opcodes::OP_CHECKSIG]);
    }

    /**
     * Create a P2PKH output script
     *
     * @param BufferInterface $pubKeyHash
     * @return ScriptInterface
     */
    public function payToPubKeyHash(BufferInterface $pubKeyHash)
    {
        if ($pubKeyHash->getSize() !== 20) {
            throw new \RuntimeException('Public key hash must be exactly 20 bytes');
        }

        return ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $pubKeyHash, Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]);
    }

    /**
    /**
     * Create a P2SH output script
     *
     * @param BufferInterface $scriptHash
     * @return ScriptInterface
     */
    public function payToScriptHash(BufferInterface $scriptHash)
    {
        if ($scriptHash->getSize() !== 20) {
            throw new \RuntimeException('P2SH scriptHash must be exactly 20 bytes');
        }

        return ScriptFactory::sequence([Opcodes::OP_HASH160, $scriptHash, Opcodes::OP_EQUAL]);
    }

    /**
     * @param int $m
     * @param PublicKeyInterface[] $keys
     * @param bool|true $sort
     * @return ScriptInterface
     */
    public function multisig($m, array $keys = [], $sort = true)
    {
        $n = count($keys);
        if ($m < 0) {
            throw new \LogicException('Number of signatures cannot be less than zero');
        }

        if ($m > $n) {
            throw new \LogicException('Required number of sigs exceeds number of public keys');
        }

        if ($n > 20) {
            throw new \LogicException('Number of public keys is greater than 16');
        }

        if ($sort) {
            $keys = Buffertools::sort($keys);
        }

        $new = ScriptFactory::create();
        $new->int($m);
        foreach ($keys as $key) {
            if (!$key instanceof PublicKeyInterface) {
                throw new \LogicException('Values in $keys[] must be a PublicKey');
            }

            $new->push($key->getBuffer());
        }

        return $new->int($n)->op('OP_CHECKMULTISIG')->getScript();
    }

    /**
     * @param BufferInterface $keyHash
     * @return ScriptInterface
     */
    public function witnessKeyHash(BufferInterface $keyHash)
    {
        if ($keyHash->getSize() !== 20) {
            throw new \RuntimeException('witness key-hash should be 20 bytes');
        }

        return ScriptFactory::sequence([Opcodes::OP_0, $keyHash]);
    }

    /**
     * @param BufferInterface $scriptHash
     * @return ScriptInterface
     */
    public function witnessScriptHash(BufferInterface $scriptHash)
    {
        if ($scriptHash->getSize() !== 32) {
            throw new \RuntimeException('witness script-hash should be 32 bytes');
        }

        return ScriptFactory::sequence([Opcodes::OP_0, $scriptHash]);
    }
}
