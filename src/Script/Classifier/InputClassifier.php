<?php

namespace Bitcoin\Script\Classifier;

use Bitcoin\Buffer;
use Bitcoin\Key\PublicKey;
use Bitcoin\Script\Script;

/**
 * Class OutputClassifier
 * @package Bitcoin\Script\Classifier
 */
class InputClassifier implements ScriptClassifierInterface
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
        return (
            count($this->evalScript) == 1
            && (strlen($this->evalScript) <= 0x47)
        );
    }

    /**
     * @return bool
     */
    public function isPayToPublicKeyHash()
    {
        return (
            count($this->evalScript) == 2
            && (strlen($this->evalScript[0]) <= 0x47)
            && PublicKey::isCompressedOrUncompressed(Buffer::hex($this->evalScript[1]))
        );
    }

    /**
     * @return bool
     */
    public function isPayToScriptHash()
    {
        if (count($this->evalScript) == 0) {
            return false;
        }

        $final = end($this->evalScript);
        if (!$final) {
            return false;
        }

        $script = new Script(Buffer::hex($final));
        $type = new self($script);
        // todo.......
        return $type->classify() !== false;
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
