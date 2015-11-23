<?php

namespace BitWasp\Bitcoin\Script\Classifier;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;

class InputClassifier implements ScriptClassifierInterface
{

    /**
     * @var Operation[]
     */
    private $decoded;

    const MAXSIGLEN = 0x48;

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
        return count($this->decoded) === 1
        && $this->decoded[0]->isPush()
        && $this->decoded[0]->getDataSize() <= self::MAXSIGLEN;
    }

    /**
     * @return bool
     */
    public function isPayToPublicKeyHash()
    {
        if (count($this->decoded) !== 2) {
            return false;
        }

        $signature = $this->decoded[0];
        $publicKey = $this->decoded[1];

        return $signature->isPush() && $signature->getDataSize() <= self::MAXSIGLEN
            && $publicKey->isPush() && PublicKey::isCompressedOrUncompressed($publicKey->getData());
    }

    /**
     * @return bool
     */
    public function isPayToScriptHash()
    {
        if (count($this->decoded) < 1) {
            return false;
        }

        $final = end($this->decoded);
        if (!$final || !$final->isPush()) {
            return false;
        }

        $type = new OutputClassifier(new Script($final->getData()));
        return false === in_array($type->classify(), [
            self::UNKNOWN,
            self::PAYTOSCRIPTHASH
        ], true);
    }

    /**
     * @return bool
     */
    public function isMultisig()
    {
        if (count($this->decoded) < 3) {
            return false;
        }

        $final = end($this->decoded);
        if (!$final || !$final->isPush()) {
            return false;
        }

        $script = new Script($final->getData());
        $decoded = $script->getScriptParser()->decode();
        $count = count($decoded);

        $mOp = $decoded[0];
        $nOp = $decoded[$count - 2];
        if ($mOp->isPush() || $nOp->isPush()) {
            return false;
        }

        if ($mOp->getOp() < Opcodes::OP_0 || $nOp->getOp() > Opcodes::OP_16) {
            return false;
        }

        /** @var Operation[] $keys */
        $keys = array_slice($decoded, 1, -2);
        $keysValid = true;
        foreach ($keys as $key) {
            $keysValid &= $key->isPush() && PublicKey::isCompressedOrUncompressed($key->getData());
        }

        return $keysValid;
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
        } elseif ($this->isMultisig()) {
            return self::MULTISIG;
        } elseif ($this->isPayToScriptHash()) {
            return self::PAYTOSCRIPTHASH;
        }

        return self::UNKNOWN;
    }
}
