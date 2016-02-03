<?php

namespace BitWasp\Bitcoin\Script\Classifier;

use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\BufferInterface;

class OutputClassifier implements ScriptClassifierInterface
{
    /**
     * @var \BitWasp\Bitcoin\Script\Parser\Operation[]
     */
    private $decoded;

    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @param ScriptInterface $script
     */
    public function __construct(ScriptInterface $script)
    {
        $this->script = $script;
        $this->decoded = $script->getScriptParser()->decode();
    }

    /**
     * @param BufferInterface|null $publicKey
     * @return bool
     */
    public function isPayToPublicKey(BufferInterface & $publicKey = null)
    {
        if (count($this->decoded) < 1 || !$this->decoded[0]->isPush()) {
            return false;
        }

        $size = $this->decoded[0]->getDataSize();
        if ($size === 33 || $size === 65) {
            $op = $this->decoded[1];
            if (!$op->isPush() && $op->getOp() === Opcodes::OP_CHECKSIG) {
                $publicKey = $this->decoded[0];
                return true;
            }
        }

        return false;
    }

    /**
     * @param BufferInterface|null $pubKeyHash
     * @return bool
     */
    public function isPayToPublicKeyHash(BufferInterface & $pubKeyHash = null)
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

        if ($dup->getOp() === Opcodes::OP_DUP
        && $hash->getOp() === Opcodes::OP_HASH160
        && $buf->isPush() && $buf->getDataSize() === 20
        && $eq->getOp() === Opcodes::OP_EQUALVERIFY
        && $checksig->getOp() === Opcodes::OP_CHECKSIG) {
            $pubKeyHash = $this->decoded[2]->getData();
            return true;
        }

        return false;
    }

    /**
     * @param BufferInterface|null $scriptHash
     * @return bool
     */
    public function isPayToScriptHash(BufferInterface & $scriptHash = null)
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
        if (!$eq->isPush() && $eq->getOp() === Opcodes::OP_EQUAL) {
            $scriptHash = $this->decoded[1]->getData();
            return true;
        }

        return false;
    }

    /**
     * @param BufferInterface[] $keys
     * @return bool
     */
    public function isMultisig(& $keys = [])
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
     * @param BufferInterface $programHash
     * @return bool
     */
    public function isWitness(BufferInterface & $programHash = null)
    {
        $buffer = $this->script->getBuffer();
        $size = $buffer->getSize();

        if ($size < 4 || $size > 34) {
            return false;
        }

        $parser = $this->script->getScriptParser();
        $script = $parser->decode();
        if (count($script) !== 2 || !$script[1]->isPush()) {
            return false;
        }

        $version = $script[0]->getOp();
        if ($version != Opcodes::OP_0 && ($version < Opcodes::OP_1 || $version > Opcodes::OP_16)) {
            return false;
        }

        $witness = $script[1];
        if ($size === $witness->getDataSize() + 2) {
            $programHash = $witness->getData();
            return true;
        }

        return false;
    }

    /**
     * @param BufferInterface|BufferInterface[] $solutions
     * @return string
     */
    public function classify(&$solutions = null)
    {
        $type = self::UNKNOWN;
        $solution = null;
        if ($this->isPayToScriptHash($solutions)) {
            /** @var BufferInterface $solution */
            $type = self::PAYTOSCRIPTHASH;
        } elseif ($this->isWitness($solutions)) {
            /** @var BufferInterface $solution */
            if ($solutions->getSize() == 20) {
                $type = ScriptClassifierInterface::WITNESS_V0_KEYHASH;
            } else {
                $type = ScriptClassifierInterface::WITNESS_V0_SCRIPTHASH;
            }
        } elseif ($this->isPayToPublicKey($solutions)) {
            /** @var BufferInterface $solution */
            return self::PAYTOPUBKEY;
        } elseif ($this->isPayToPublicKeyHash($solutions)) {
            /** @var BufferInterface $solution */
            return self::PAYTOPUBKEYHASH;
        } elseif ($this->isMultisig($solutions)) {
            /** @var BufferInterface[] $solution */
            return self::MULTISIG;
        }

        return $type;
    }
}
