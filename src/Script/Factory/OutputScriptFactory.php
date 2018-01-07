<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;

class OutputScriptFactory
{
    /**
     * @param PublicKeyInterface $publicKey
     * @return ScriptInterface
     */
    public function p2pk(PublicKeyInterface $publicKey): ScriptInterface
    {
        return $this->payToPubKey($publicKey);
    }

    /**
     * @param BufferInterface $pubKeyHash
     * @return ScriptInterface
     */
    public function p2pkh(BufferInterface $pubKeyHash): ScriptInterface
    {
        return $this->payToPubKeyHash($pubKeyHash);
    }

    /**
     * @param BufferInterface $scriptHash
     * @return ScriptInterface
     */
    public function p2sh(BufferInterface $scriptHash): ScriptInterface
    {
        return $this->payToScriptHash($scriptHash);
    }

    /**
     * @param BufferInterface $witnessScriptHash
     * @return ScriptInterface
     */
    public function p2wsh(BufferInterface $witnessScriptHash): ScriptInterface
    {
        return $this->witnessScriptHash($witnessScriptHash);
    }

    /**
     * @param BufferInterface $witnessKeyHash
     * @return ScriptInterface
     */
    public function p2wkh(BufferInterface $witnessKeyHash): ScriptInterface
    {
        return $this->witnessKeyHash($witnessKeyHash);
    }
    /**
     * Create a Pay to pubkey output
     *
     * @param PublicKeyInterface  $publicKey
     * @return ScriptInterface
     */
    public function payToPubKey(PublicKeyInterface $publicKey): ScriptInterface
    {
        return ScriptFactory::sequence([$publicKey->getBuffer(), Opcodes::OP_CHECKSIG]);
    }

    /**
     * Create a P2PKH output script
     *
     * @param BufferInterface $pubKeyHash
     * @return ScriptInterface
     */
    public function payToPubKeyHash(BufferInterface $pubKeyHash): ScriptInterface
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
    public function payToScriptHash(BufferInterface $scriptHash): ScriptInterface
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
    public function multisig(int $m, array $keys = [], bool $sort = true): ScriptInterface
    {
        return self::multisigKeyBuffers($m, array_map(function (PublicKeyInterface $key): BufferInterface {
            return $key->getBuffer();
        }, $keys), $sort);
    }

    /**
     * @param int $m
     * @param BufferInterface[] $keys
     * @param bool $sort
     * @return ScriptInterface
     */
    public function multisigKeyBuffers(int $m, array $keys = [], bool $sort = true): ScriptInterface
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
            if ($key->getSize() !== PublicKey::LENGTH_COMPRESSED && $key->getSize() !== PublicKey::LENGTH_UNCOMPRESSED) {
                throw new \RuntimeException("Invalid length for public key buffer");
            }

            $new->push($key);
        }

        return $new->int($n)->opcode(Opcodes::OP_CHECKMULTISIG)->getScript();
    }

    /**
     * @param BufferInterface $keyHash
     * @return ScriptInterface
     */
    public function witnessKeyHash(BufferInterface $keyHash): ScriptInterface
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
    public function witnessScriptHash(BufferInterface $scriptHash): ScriptInterface
    {
        if ($scriptHash->getSize() !== 32) {
            throw new \RuntimeException('witness script-hash should be 32 bytes');
        }

        return ScriptFactory::sequence([Opcodes::OP_0, $scriptHash]);
    }

    /**
     * @param BufferInterface $commitment
     * @return ScriptInterface
     */
    public function witnessCoinbaseCommitment(BufferInterface $commitment): ScriptInterface
    {
        if ($commitment->getSize() !== 32) {
            throw new \RuntimeException('Witness commitment hash must be exactly 32-bytes');
        }

        return ScriptFactory::sequence([
            Opcodes::OP_RETURN,
            new Buffer("\xaa\x21\xa9\xed" . $commitment->getBinary())
        ]);
    }
}
