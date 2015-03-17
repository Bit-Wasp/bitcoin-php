<?php

namespace Afk11\Bitcoin\Script\Classifier;

use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Key\PublicKey;
use Afk11\Bitcoin\Script\Script;
use Afk11\Bitcoin\Script\ScriptInterface;

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
            return InputClassifier::PAYTOPUBKEY;
        } elseif ($this->isPayToPublicKeyHash()) {
            return InputClassifier::PAYTOPUBKEYHASH;
        } elseif ($this->isPayToScriptHash()) {
            return InputClassifier::PAYTOSCRIPTHASH;
        } elseif ($this->isMultisig()) {
            return InputClassifier::MULTISIG;
        }

        return InputClassifier::UNKNOWN;
    }
}
