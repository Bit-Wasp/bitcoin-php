<?php

namespace BitWasp\Bitcoin\Script\Classifier;

use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptInterface;

class OutputClassifier implements ScriptClassifierInterface
{
    /**
     * @var \BitWasp\Bitcoin\Script\Parser\Operation[]
     */
    private $decoded;

    /**
     * @param ScriptInterface $script
     */
    public function __construct(ScriptInterface $script)
    {
        $this->decoded = $script->getScriptParser()->decode();
    }

    /**
     * @return bool
     */
    public function isPayToPublicKey()
    {
        if (count($this->decoded) < 1 || !$this->decoded[0]->isPush()) {
            return false;
        }

        $size = $this->decoded[0]->getDataSize();
        if ($size === 33 || $size === 65) {
            $op = $this->decoded[1];
            if (!$op->isPush() && $op->getOp() === Opcodes::OP_CHECKSIG) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isPayToPublicKeyHash()
    {
        if (count($this->decoded) !== 5) {
            return false;
        }

        $dup = $this->decoded[0];
        $hash = $this->decoded[1];
        $buf = $this->decoded[2];
        $eq = $this->decoded[3];
        $checksig = $this->decoded[4];

        foreach ([$dup, $hash, $eq, $checksig] as $op) {
            /** @var Operation $op */
            if ($op->isPush()) {
                return false;
            }
        }

        return $dup->getOp() === Opcodes::OP_DUP
        && $hash->getOp() === Opcodes::OP_HASH160
        && $buf->isPush() && $buf->getDataSize() === 20
        && $eq->getOp() === Opcodes::OP_EQUALVERIFY
        && $checksig->getOp() === Opcodes::OP_CHECKSIG;
    }

    /**
     * @return bool
     */
    public function isPayToScriptHash()
    {
        if (count($this->decoded) !== 3) {
            return false;
        }

        $hash = $this->decoded[0];
        if ($hash->isPush() || !$hash->getOp() === Opcodes::OP_HASH160) {
            return false;
        }

        $buffer = $this->decoded[1];
        if (!$buffer->isPush() || $buffer->getDataSize() !== 20) {
            return false;
        }

        $eq = $this->decoded[2];
        return !$eq->isPush() && $eq->getOp() === Opcodes::OP_EQUAL;
    }

    /**
     * @return bool
     */
    public function isMultisig()
    {
        $count = count($this->decoded);
        if ($count <= 3) {
            return false;
        }

        $mOp = $this->decoded[0];
        $nOp = $this->decoded[$count - 2];
        $checksig = $this->decoded[$count - 1];
        if ($mOp->isPush() || $nOp->isPush() || $checksig->isPush()) {
            return false;
        }

        /** @var Operation[] $vKeys */
        $vKeys = array_slice($this->decoded, 1, -2);
        foreach ($vKeys as $key) {
            if (!$key->isPush() || !PublicKey::isCompressedOrUncompressed($key->getData())) {
                return false;
            }
        }

        return $mOp->getOp() >= Opcodes::OP_0
            && $nOp->getOp() <= Opcodes::OP_16
            && $checksig->getOp() === Opcodes::OP_CHECKMULTISIG;
    }

    /**
     * @return string
     */
    public function classify()
    {
        if ($this->isPayToPublicKey()) {
            return self::PAYTOPUBKEY;
        } elseif ($this->isPayToPublicKeyHash()) {
            return self::PAYTOPUBKEYHASH;
        } elseif ($this->isPayToScriptHash()) {
            return self::PAYTOSCRIPTHASH;
        } elseif ($this->isMultisig()) {
            return self::MULTISIG;
        }

        return self::UNKNOWN;
    }
}
