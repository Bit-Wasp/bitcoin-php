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

    /**
     * @param Operation[] $decoded
     * @return false|BufferInterface
     */
    private function decodeP2PK(array $decoded)
    {
        if (count($decoded) !== 2 || !$decoded[0]->isPush()) {
            return false;
        }

        $size = $decoded[0]->getDataSize();
        if ($size === 33 || $size === 65) {
            $op = $decoded[1];
            if ($op->getOp() === Opcodes::OP_CHECKSIG) {
                return $decoded[0]->getData();
            }
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @return bool
     */
    public function isPayToPublicKey(ScriptInterface $script)
    {
        try {
            return $this->decodeP2PK($script->getScriptParser()->decode()) !== false;
        } catch (\Exception $e) {
            /** Return false later */
        }

        return false;
    }

    /**
     * @param Operation[] $decoded
     * @return BufferInterface|false
     */
    private function decodeP2PKH(array $decoded)
    {
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
            return $decoded[2]->getData();
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @return bool
     */
    public function isPayToPublicKeyHash(ScriptInterface $script)
    {
        try {
            return $this->decodeP2PKH($script->getScriptParser()->decode()) !== false;
        } catch (\Exception $e) {
            /** Return false later */
        }

        return false;
    }

    /**
     * @param array $decoded
     * @return bool
     */
    private function decodeP2SH(array $decoded)
    {
        if (count($decoded) !== 3) {
            return false;
        }

        $op_hash = $decoded[0];
        if ($op_hash->isPush() || $op_hash->getOp() !== Opcodes::OP_HASH160) {
            return false;
        }

        $buffer = $decoded[1];
        if (!$buffer->isPush() || $buffer->getOp() !== 20) {
            return false;
        }

        $eq = $decoded[2];
        if (!$eq->isPush() && $eq->getOp() === Opcodes::OP_EQUAL) {
            return $decoded[1]->getData();
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @return bool
     */
    public function isPayToScriptHash(ScriptInterface $script)
    {
        try {
            return $this->decodeP2SH($script->getScriptParser()->decode()) !== false;
        } catch (\Exception $e) {
            /** Return false later */
        }

        return false;
    }

    /**
     * @param Operation[] $decoded
     * @return bool|BufferInterface[]
     */
    private function decodeMultisig(array $decoded)
    {
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
            return $solutions;
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @return bool
     */
    public function isMultisig(ScriptInterface $script)
    {
        try {
            return $this->decodeMultisig($script->getScriptParser()->decode()) !== false;
        } catch (\Exception $e) {
            /** Return false later */
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @param Operation[] $decoded
     * @return false|BufferInterface
     */
    private function decodeWitnessNoLimit(ScriptInterface $script, array $decoded)
    {
        $size = $script->getBuffer()->getSize();
        if ($size < 4 || $size > 40) {
            return false;
        }
        if (count($decoded) !== 2 || !$decoded[1]->isPush()) {
            return false;
        }

        $version = $decoded[0]->getOp();
        if ($version !== Opcodes::OP_0 && ($version < Opcodes::OP_1 || $version > Opcodes::OP_16)) {
            return false;
        }

        $witness = $decoded[1];
        if ($size === $witness->getDataSize() + 2) {
            return $witness->getData();
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @param int $limit
     * @param array $decoded
     * @return BufferInterface|false
     */
    private function decodeWithLimit(ScriptInterface $script, $limit, array $decoded)
    {
        if (($data = $this->decodeWitnessNoLimit($script, $decoded))) {
            if ($data->getSize() !== $limit) {
                return false;
            }

            return $data;
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @param Operation[] $decoded
     * @return BufferInterface|false
     */
    private function decodeP2WKH(ScriptInterface $script, array $decoded)
    {
        return $this->decodeWithLimit($script, 20, $decoded);
    }

    /**
     * @param ScriptInterface $script
     * @param Operation[] $decoded
     * @return BufferInterface|false
     */
    private function decodeP2WSH(ScriptInterface $script, array $decoded)
    {
        return $this->decodeWithLimit($script, 32, $decoded);
    }

    /**
     * @param ScriptInterface $script
     * @return bool
     */
    public function isWitness(ScriptInterface $script)
    {
        try {
            return $this->decodeWitnessNoLimit($script, $script->getScriptParser()->decode())!== false;
        } catch (\Exception $e) {
            /** Return false later */
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @param mixed $solution
     * @return string
     */
    public function classify(ScriptInterface $script, &$solution = null)
    {
        $decoded = $script->getScriptParser()->decode();
        $type = self::UNKNOWN;
        $solution = null;

        if (($pubKey = $this->decodeP2PK($decoded))) {
            $type = self::PAYTOPUBKEY;
            $solution = $pubKey;
        } else if (($pubKeyHash = $this->decodeP2PKH($decoded))) {
            $type = self::PAYTOPUBKEYHASH;
            $solution = $pubKeyHash;
        } else if (($multisig = $this->decodeMultisig($decoded))) {
            $type = self::MULTISIG;
            $solution = $multisig;
        } else if (($scriptHash = $this->decodeP2SH($decoded))) {
            $type = self::PAYTOSCRIPTHASH;
            $solution = $scriptHash;
        } else if (($witnessScriptHash = $this->decodeP2WSH($script, $decoded))) {
            $type = self::WITNESS_V0_SCRIPTHASH;
            $solution = $witnessScriptHash;
        } else if (($witnessKeyHash = $this->decodeP2WKH($script, $decoded))) {
            $type = self::WITNESS_V0_KEYHASH;
            $solution = $witnessKeyHash;
        }

        return $type;
    }

    /**
     * @param ScriptInterface $script
     * @return OutputData
     */
    public function decode(ScriptInterface $script)
    {
        $solution = null;
        $type = $this->classify($script, $solution);
        return new OutputData($type, $script, $solution);
    }
}
