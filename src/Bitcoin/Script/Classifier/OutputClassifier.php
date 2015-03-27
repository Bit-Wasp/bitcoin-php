<?php

namespace BitWasp\Bitcoin\Script\Classifier;

use BitWasp\Bitcoin\Key\PublicKey;
use BitWasp\Bitcoin\Script\ScriptInterface;

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

        if (strlen($script) == 35 // Binary
            && strlen($this->evalScript[0]) == 33 * 2 // hex string
            && $this->evalScript[1] == 'OP_CHECKSIG'
            && (in_array(ord($script[1]), array(PublicKey::KEY_COMPRESSED_EVEN, PublicKey::KEY_COMPRESSED_ODD)))
        ) {
            return true;
        }

        if (strlen($script) == 67
            && strlen($this->evalScript[0]) == 65 * 2
            && $this->evalScript[1] == 'OP_CHECKSIG'
            && $script[1] == PublicKey::KEY_UNCOMPRESSED
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
        return (
            count($this->evalScript) == 5
            && $this->evalScript[0] == 'OP_DUP'
            && $this->evalScript[1] == 'OP_HASH160'
            && strlen($this->evalScript[2]) == 20 * 2 // hex string
            && ($this->evalScript[3] == 'OP_EQUALVERIFY')
            && $this->evalScript[4] == 'OP_CHECKSIG'
        );
    }

    /**
     * @return bool
     */
    public function isPayToScriptHash()
    {
        return (
            strlen($this->script->getBuffer()->getBinary()) == 23
            && count($this->evalScript) == 3
            && $this->evalScript[0] == 'OP_HASH160'
            && (strlen($this->evalScript[1]) == 20 * 2)
            && $this->evalScript[2] == 'OP_EQUAL'
        );
    }

    /**
     * @return bool
     */
    public function isMultisig()
    {
        return false;
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

        return self::NONSTANDARD;
    }
}
