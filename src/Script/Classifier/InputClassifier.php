<?php

namespace BitWasp\Bitcoin\Script\Classifier;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;

class InputClassifier implements ScriptClassifierInterface
{

    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @var array
     */
    private $evalScript;

    const MAXSIGLEN = 0x48;

    /**
     * @param ScriptInterface $script
     */
    public function __construct(ScriptInterface $script)
    {
        $this->script = $script;
        $this->evalScript = $script->getScriptParser()->parse();
    }

    /**
     * @return bool
     */
    public function isPayToPublicKey()
    {
        return count($this->evalScript) === 1
            && $this->evalScript[0] instanceof Buffer
            && $this->evalScript[0]->getSize() <= self::MAXSIGLEN;
    }

    /**
     * @return bool
     */
    public function isPayToPublicKeyHash()
    {
        return count($this->evalScript) === 2
            && $this->evalScript[0] instanceof Buffer && $this->evalScript[1] instanceof Buffer
            && $this->evalScript[0]->getSize() <= self::MAXSIGLEN
            && PublicKey::isCompressedOrUncompressed($this->evalScript[1]);
    }

    /**
     * @return bool
     */
    public function isPayToScriptHash()
    {
        if (count($this->evalScript) === 0) {
            return false;
        }

        $final = end($this->evalScript);
        if (!$final || !$final instanceof Buffer) {
            return false;
        }

        $type = new OutputClassifier(new Script($final));
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
        if (count($this->evalScript) < 3) {
            return false;
        }

        $final = end($this->evalScript);
        if (!$final || !$final instanceof Buffer) {
            return false;
        }

        $script = new Script($final);
        $parsed = $script->getScriptParser()->parse();
        $count = count($parsed);
        $opCodes = $script->getOpCodes();

        /** @var string $mOp */
        $mOp = $parsed[0];
        /** @var string $nOp */
        $nOp = $parsed[$count - 2];
        if ($mOp instanceof Buffer || $nOp instanceof Buffer){
            return false;
        }
        $mOp = $opCodes->getOpByName($mOp);
        $nOp = $opCodes->getOpByName($nOp);
        if ($opCodes->cmp($mOp, 'OP_0') < 0 || $opCodes->cmp($nOp, 'OP_16') > 0) {
            return false;
        }

        $keys = array_slice($parsed, 1, -2);
        $keysValid = true;
        foreach ($keys as $key) {
            $keysValid &= ($key instanceof Buffer) && PublicKey::isCompressedOrUncompressed($key);
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
