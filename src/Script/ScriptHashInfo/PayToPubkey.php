<?php

namespace BitWasp\Bitcoin\Script\ScriptHashInfo;

use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;

class PayToPubkey implements ScriptInfoInterface
{
    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @var PublicKeyInterface
     */
    private $publicKey;

    /**
     * @param ScriptInterface $script
     */
    public function __construct(ScriptInterface $script)
    {
        $this->script = $script;
        $chunks = $script->getScriptParser()->parse();
        $this->publicKey = PublicKeyFactory::fromHex($chunks[0]);
    }

    /**
     * @return string
     */
    public function classification()
    {
        return OutputClassifier::PAYTOPUBKEY;
    }

    /**
     * @return int
     */
    public function getRequiredSigCount()
    {
        return 1;
    }

    /**
     * @return int
     */
    public function getKeyCount()
    {
        return 1;
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @return bool
     */
    public function checkInvolvesKey(PublicKeyInterface $publicKey)
    {
        return $publicKey->getBinary() === $this->publicKey->getBinary();
    }

    /**
     * @return \BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface[]
     */
    public function getKeys()
    {
        return [$this->publicKey];
    }
}
