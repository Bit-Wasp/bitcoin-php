<?php

namespace BitWasp\Bitcoin\Script\Classifier;

use BitWasp\Bitcoin\Key\PublicKey;
use BitWasp\Bitcoin\Script\RedeemScript;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffer;

class OutputClassifier implements ScriptClassifierInterface
{

    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @var array
     */
    private $evalScript;

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
        $script = $this->script->getBuffer()->getBinary();
        if (!isset($this->evalScript[0]) || !$this->evalScript[0] instanceof Buffer) {
            return false;
        }

        if (strlen($script) == 35
            && $this->evalScript[0]->getSize() == 33
            && $this->evalScript[1] == 'OP_CHECKSIG'
            && in_array(ord($script[1]), array(PublicKey::KEY_COMPRESSED_EVEN, PublicKey::KEY_COMPRESSED_ODD))
        ) {
            return true;
        }

        if (strlen($script) == 67
            && $this->evalScript[0]->getSize() == 65
            && $this->evalScript[1] == 'OP_CHECKSIG'
            && bin2hex($script[1]) == PublicKey::KEY_UNCOMPRESSED
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isPayToPublicKeyHash()
    {
        return count($this->evalScript) == 5
            && is_string($this->evalScript[0])
            && $this->evalScript[0] == 'OP_DUP'
            && is_string($this->evalScript[1])
            && $this->evalScript[1] == 'OP_HASH160'
            && $this->evalScript[2] instanceof Buffer
            && $this->evalScript[2]->getSize() == 20 // hex string
            && is_string($this->evalScript[3])
            && $this->evalScript[3] == 'OP_EQUALVERIFY'
            && is_string($this->evalScript[4])
            && $this->evalScript[4] == 'OP_CHECKSIG';
    }

    /**
     * @return bool
     */
    public function isPayToScriptHash()
    {
        return $this->script->getBuffer()->getSize() == 23
            && count($this->evalScript) == 3
            && is_string($this->evalScript[0]) && is_string($this->evalScript[2])
            && $this->evalScript[0] == 'OP_HASH160'
            && $this->evalScript[1] instanceof Buffer
            && $this->evalScript[1]->getSize() == 20
            && $this->evalScript[2] == 'OP_EQUAL';
    }

    /**
     * @return bool
     */
    public function isMultisig()
    {
        $opCodes = $this->script->getOpcodes();
        $count = count($this->evalScript);
        if ($count <= 3) {
            return false;
        }
        $mOp = $this->evalScript[0];
        $nOp = $this->evalScript[$count - 2];
        $lastOp = $this->evalScript[$count - 1];

        $keys = array_slice($this->evalScript, 1, -2);
        $keysValid = function () use ($keys) {
            $valid = true;
            foreach ($keys as $key) {
                $valid &= ($key instanceof Buffer) && PublicKey::isCompressedOrUncompressed($key);
            }
            return $valid;
        };

        return $count >= 2
            && is_string($mOp) && is_string($nOp) && is_string($lastOp)
            && $opCodes->cmp($opCodes->getOpByName($mOp), 'OP_0') >= 0
            && $opCodes->cmp($opCodes->getOpByName($nOp), 'OP_16') <= 0
            && $this->evalScript[$count - 1] == 'OP_CHECKMULTISIG'
            && $keysValid();
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
