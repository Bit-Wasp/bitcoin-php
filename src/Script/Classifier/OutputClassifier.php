<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script\Classifier;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class OutputClassifier
{
    /**
     * @deprecated
     */
    const PAYTOPUBKEY = 'pubkey';

    /**
     * @deprecated
     */
    const PAYTOPUBKEYHASH = 'pubkeyhash';

    /**
     * @deprecated
     */
    const PAYTOSCRIPTHASH = 'scripthash';

    /**
     * @deprecated
     */
    const WITNESS_V0_KEYHASH = 'witness_v0_keyhash';

    /**
     * @deprecated
     */
    const WITNESS_V0_SCRIPTHASH = 'witness_v0_scripthash';

    /**
     * @deprecated
     */
    const MULTISIG = 'multisig';

    /**
     * @deprecated
     */
    const NULLDATA = 'nulldata';

    /**
     * @deprecated
     */
    const UNKNOWN = 'nonstandard';

    /**
     * @deprecated
     */
    const NONSTANDARD = 'nonstandard';

    /**
     * @deprecated
     */
    const P2PK = 'pubkey';

    /**
     * @deprecated
     */
    const P2PKH = 'pubkeyhash';

    /**
     * @deprecated
     */
    const P2SH = 'scripthash';

    /**
     * @deprecated
     */
    const P2WSH = 'witness_v0_scripthash';

    /**
     * @deprecated
     */
    const P2WKH = 'witness_v0_keyhash';

    /**
     * @deprecated
     */
    const WITNESS_COINBASE_COMMITMENT = 'witness_coinbase_commitment';

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
    public function isPayToPublicKey(ScriptInterface $script): bool
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
    public function isPayToPublicKeyHash(ScriptInterface $script): bool
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
     * @return bool|BufferInterface
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
    public function isPayToScriptHash(ScriptInterface $script): bool
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
    public function isMultisig(ScriptInterface $script): bool
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
     * @param Operation[] $decoded
     * @return BufferInterface|false
     */
    private function decodeP2WKH2(array $decoded)
    {
        if (count($decoded) === 2
            && $decoded[0]->getOp() === Opcodes::OP_0
            && $decoded[1]->isPush()
            && $decoded[1]->getDataSize() === 20) {
            return $decoded[1]->getData();
        }

        return false;
    }

    /**
     * @param Operation[] $decoded
     * @return BufferInterface|false
     */
    private function decodeP2WSH2(array $decoded)
    {
        if (count($decoded) === 2
            && $decoded[0]->getOp() === Opcodes::OP_0
            && $decoded[1]->isPush()
            && $decoded[1]->getDataSize() === 32) {
            return $decoded[1]->getData();
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @return bool
     */
    public function isWitness(ScriptInterface $script): bool
    {
        try {
            return $this->decodeWitnessNoLimit($script, $script->getScriptParser()->decode())!== false;
        } catch (\Exception $e) {
            /** Return false later */
        }

        return false;
    }

    /**
     * @param Operation[] $decoded
     * @return false|BufferInterface
     */
    private function decodeNullData(array $decoded)
    {
        if (count($decoded) !== 2) {
            return false;
        }

        if ($decoded[0]->getOp() === Opcodes::OP_RETURN && $decoded[1]->isPush()) {
            return $decoded[1]->getData();
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @return bool
     */
    public function isNullData(ScriptInterface $script): bool
    {
        try {
            return $this->decodeNullData($script->getScriptParser()->decode()) !== false;
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * @param array $decoded
     * @return bool|BufferInterface
     */
    private function decodeWitnessCoinbaseCommitment(array $decoded)
    {
        if (count($decoded) !== 2) {
            return false;
        }

        if ($decoded[0]->isPush() || $decoded[0]->getOp() !== Opcodes::OP_RETURN) {
            return false;
        }

        if ($decoded[1]->isPush()) {
            $data = $decoded[1]->getData()->getBinary();
            if ($decoded[1]->getDataSize() === 0x24 && substr($data, 0, 4) === "\xaa\x21\xa9\xed") {
                return new Buffer(substr($data, 4));
            }
        }

        return false;
    }

    /**
     * @param ScriptInterface $script
     * @return bool
     */
    public function isWitnessCoinbaseCommitment(ScriptInterface $script): bool
    {
        try {
            return $this->decodeWitnessCoinbaseCommitment($script->getScriptParser()->decode()) !== false;
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * @param array $decoded
     * @param null $solution
     * @return string
     */
    private function classifyDecoded(array $decoded, &$solution = null): string
    {
        $type = ScriptType::NONSTANDARD;

        if (($pubKey = $this->decodeP2PK($decoded))) {
            $type = ScriptType::P2PK;
            $solution = $pubKey;
        } else if (($pubKeyHash = $this->decodeP2PKH($decoded))) {
            $type = ScriptType::P2PKH;
            $solution = $pubKeyHash;
        } else if (($multisig = $this->decodeMultisig($decoded))) {
            $type = ScriptType::MULTISIG;
            $solution = $multisig;
        } else if (($scriptHash = $this->decodeP2SH($decoded))) {
            $type = ScriptType::P2SH;
            $solution = $scriptHash;
        } else if (($witnessScriptHash = $this->decodeP2WSH2($decoded))) {
            $type = ScriptType::P2WSH;
            $solution = $witnessScriptHash;
        } else if (($witnessKeyHash = $this->decodeP2WKH2($decoded))) {
            $type = ScriptType::P2WKH;
            $solution = $witnessKeyHash;
        } else if (($witCommitHash = $this->decodeWitnessCoinbaseCommitment($decoded))) {
            $type = ScriptType::WITNESS_COINBASE_COMMITMENT;
            $solution = $witCommitHash;
        } else if (($nullData = $this->decodeNullData($decoded))) {
            $type = ScriptType::NULLDATA;
            $solution = $nullData;
        }

        return $type;
    }

    /**
     * @param ScriptInterface $script
     * @param mixed $solution
     * @return string
     */
    public function classify(ScriptInterface $script, &$solution = null): string
    {
        $decoded = $script->getScriptParser()->decode();

        $type = $this->classifyDecoded($decoded, $solution);

        return $type;
    }

    /**
     * @param ScriptInterface $script
     * @return OutputData
     */
    public function decode(ScriptInterface $script): OutputData
    {
        $solution = null;
        $type = $this->classify($script, $solution);
        return new OutputData($type, $script, $solution);
    }

    /**
     * @param ScriptInterface $script
     * @param bool $allowNonstandard
     * @return OutputData[]
     */
    public function decodeSequence(ScriptInterface $script, bool $allowNonstandard = false): array
    {
        $decoded = $script->getScriptParser()->decode();

        $j = 0;
        $l = count($decoded);
        $result = [];
        while ($j < $l) {
            $type = null;
            $slice = null;
            $solution = null;

            // increment the $last, and break if it's valid
            for ($i = 0; $i < ($l - $j) + 1; $i++) {
                $slice = array_slice($decoded, $j, $i);
                $chkType = $this->classifyDecoded($slice, $solution);
                if ($chkType !== ScriptType::NONSTANDARD) {
                    $type = $chkType;
                    break;
                }
            }

            if (null === $type) {
                if (!$allowNonstandard) {
                    throw new \RuntimeException("Unable to classify script as a sequence of templated types");
                }
                $j++;
            } else {
                $j += $i;
                /** @var Operation[] $slice */
                /** @var mixed $solution */
                $result[] = new OutputData($type, ScriptFactory::fromOperations($slice), $solution);
            }
        }

        return $result;
    }
}
