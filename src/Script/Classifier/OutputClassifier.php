<?php

namespace Afk11\Bitcoin\Script\Classifier;

use Afk11\Bitcoin\Key\PublicKey;
use Afk11\Bitcoin\Script\Script;

class OutputClassifier implements ScriptClassifierInterface
{

    /**
     * @var Script
     */
    private $script;

    /**
     * @var array
     */
    private $evalScript;

    /**
     * @param Script $script
     */
    public function __construct(Script $script)
    {
        $this->script = $script;
        $this->evalScript = $script->parse();
    }

    /**
     * @return bool
     */
    public function isPayToPublicKey()
    {
        $script = $this->script->serialize();

        if (strlen($script) == 35 // Binary
            && strlen($this->evalScript[0]) == 33*2 // hex string
            && $this->evalScript[1] == 'OP_CHECKSIG'
            && (in_array(ord($script[1]), array(PublicKey::KEY_COMPRESSED_EVEN, PublicKey::KEY_COMPRESSED_ODD)))
        ) {
            return true;
        }

        if (strlen($script) == 67
            && strlen($this->evalScript[0]) == 65*2
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
            && strlen($this->evalScript[2]) == 20*2 // hex string
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
            strlen($this->script->serialize()) == 23
            && count($this->evalScript) == 3
            && $this->evalScript[0] == 'OP_HASH160'
            && (strlen($this->evalScript[1]) == 20*2)
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
            return ScriptClassifierInterface::PAYTOPUBKEY;
        } elseif ($this->isPayToPublicKeyHash()) {
            return ScriptClassifierInterface::PAYTOPUBKEYHASH;
        } elseif ($this->isPayToScriptHash()) {
            return ScriptClassifierInterface::PAYTOSCRIPTHASH;
        } elseif ($this->isMultisig()) {
            return ScriptClassifierInterface::MULTISIG;
        }

        return ScriptClassifierInterface::UNKNOWN;
    }
}
