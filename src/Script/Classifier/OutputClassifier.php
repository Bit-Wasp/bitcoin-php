<?php

namespace BitWasp\Bitcoin\Script\Classifier;

use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\BufferInterface;

class OutputClassifier
{
    const PAYTOPUBKEY = 'pubkey';
    const PAYTOPUBKEYHASH = 'pubkeyhash';
    const PAYTOSCRIPTHASH = 'scripthash';
    const WITNESS_V0_KEYHASH = 'witness_v0_keyhash';
    const WITNESS_V0_SCRIPTHASH = 'witness_v0_scripthash';
    const MULTISIG = 'multisig';
    const UNKNOWN = 'unknown';
    const NONSTANDARD = 'nonstandard';

    /**
     * @param ScriptInterface $script
     * @param BufferInterface $publicKey
     * @return bool
     */
    public function isPayToPublicKey(ScriptInterface $script, & $publicKey = null)
    {
        $decoded = $script->getScriptParser()->decode();
        if (count($decoded) !== 2 || $decoded[0]->isPush() === false || $decoded[1]->isPush() === true) {
            return false;
        }

        $size = $decoded[0]->getDataSize();
        if ($size === 33 || $size === 65) {
            $op = $decoded[1];
            if ($op->getOp() === Opcodes::OP_CHECKSIG) {
                $publicKey = $decoded[0]->getData();
                return true;
            }
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @param null $pubKeyHash
     * @return bool
     */
    public function isPayToPublicKeyHash(ScriptInterface $script, & $pubKeyHash = null)
    {
        $decoded = $script->getScriptParser()->decode();
        if (count($decoded) !== 5) {
            return false;
        }

        $dup = $decoded[0];
        $hash = $decoded[1];
        $buf = $decoded[2];
        $eq = $decoded[3];
        $checksig = $decoded[4];

        foreach ([$dup, $hash, $eq, $checksig] as $op) {
            /** @var Operation $op */
            if ($op->isPush()) {
                return false;
            }
        }

        if ($dup->getOp() === Opcodes::OP_DUP
        && $hash->getOp() === Opcodes::OP_HASH160
        && $buf->isPush() && $buf->getDataSize() === 20
        && $eq->getOp() === Opcodes::OP_EQUALVERIFY
        && $checksig->getOp() === Opcodes::OP_CHECKSIG) {
            $pubKeyHash = $decoded[2]->getData();
            return true;
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @param null $scriptHash
     * @return bool
     */
    public function isPayToScriptHash(ScriptInterface $script, & $scriptHash = null)
    {
        $decoded = $script->getScriptParser()->decode();
        if (count($decoded) !== 3) {
            return false;
        }

        $hash = $decoded[0];
        if ($hash->isPush() || !$hash->getOp() === Opcodes::OP_HASH160) {
            return false;
        }

        $buffer = $decoded[1];
        if (!$buffer->isPush() || $buffer->getDataSize() !== 20) {
            return false;
        }

        $eq = $decoded[2];
        if (!$eq->isPush() && $eq->getOp() === Opcodes::OP_EQUAL) {
            $scriptHash = $decoded[1]->getData();
            return true;
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @param array $keys
     * @return bool
     */
    public function isMultisig(ScriptInterface $script, & $keys = [])
    {
        $decoded = $script->getScriptParser()->decode();
        $count = count($decoded);
        if ($count <= 3) {
            return false;
        }

        $mOp = $decoded[0];
        $nOp = $decoded[$count - 2];
        $checksig = $decoded[$count - 1];
        if ($mOp->isPush() || $nOp->isPush() || $checksig->isPush()) {
            return false;
        }

        /** @var Operation[] $vKeys */
        $vKeys = array_slice($decoded, 1, -2);
        $solutions = [];
        foreach ($vKeys as $key) {
            if (!$key->isPush() || !PublicKey::isCompressedOrUncompressed($key->getData())) {
                return false;
            }
            $solutions[] = $key->getData();
        }

        if ($mOp->getOp() >= Opcodes::OP_0
            && $nOp->getOp() <= Opcodes::OP_16
            && $checksig->getOp() === Opcodes::OP_CHECKMULTISIG) {
            $keys = $solutions;
            return true;
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @param null $programHash
     * @return bool
     */
    public function isWitness(ScriptInterface $script, & $programHash = null)
    {
        $decoded = $script->getScriptParser()->decode();
        $size = $script->getBuffer()->getSize();
        if ($size < 4 || $size > 34) {
            return false;
        }

        if (count($decoded) !== 2 || !$decoded[1]->isPush()) {
            return false;
        }

        $version = $decoded[0]->getOp();
        if ($version != Opcodes::OP_0 && ($version < Opcodes::OP_1 || $version > Opcodes::OP_16)) {
            return false;
        }

        $witness = $decoded[1];
        if ($size === $witness->getDataSize() + 2) {
            $programHash = $witness->getData();
            return true;
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @param null $solution
     * @return string
     */
    public function classify(ScriptInterface $script, &$solution = null)
    {
        $type = self::UNKNOWN;
        $solution = null;
        if ($this->isPayToScriptHash($script, $solution)) {
            /** @var BufferInterface $solution */
            $type = self::PAYTOSCRIPTHASH;
        } elseif ($this->isWitness($script, $solution)) {
            /** @var BufferInterface $solution */
            if ($solution->getSize() == 20) {
                $type = self::WITNESS_V0_KEYHASH;
            } else {
                $type = self::WITNESS_V0_SCRIPTHASH;
            }
        } elseif ($this->isPayToPublicKey($script, $solution)) {
            /** @var BufferInterface $solution */
            $type = self::PAYTOPUBKEY;
        } elseif ($this->isPayToPublicKeyHash($script, $solution)) {
            /** @var BufferInterface $solution */
            $type = self::PAYTOPUBKEYHASH;
        } elseif ($this->isMultisig($script, $solution)) {
            /** @var BufferInterface[] $solution */
            $type = self::MULTISIG;
        }

        return $type;
    }
}
