<?php

namespace BitWasp\Bitcoin\Script\ScriptInfo;

use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;

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
        $chunks = $script->getScriptParser()->decode();
        if (count($chunks) < 1 || !$chunks[0]->isPush()) {
            throw new \InvalidArgumentException('Malformed pay-to-pubkey script');
        }
        $this->publicKey = PublicKeyFactory::fromHex($chunks[0]->getData());
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
     * @return PublicKeyInterface[]
     */
    public function getKeys()
    {
        return [$this->publicKey];
    }

    /**
     * @param TransactionSignatureInterface[] $signatures
     * @param PublicKeyInterface[] $publicKeys
     * @return Script|ScriptInterface
     */
    public function makeScriptSig(array $signatures = [], array $publicKeys = [])
    {
        $newScript = new Script();
        if (count($signatures) === $this->getRequiredSigCount()) {
            $newScript = ScriptFactory::sequence([$signatures[0]->getBuffer()]);
        }

        return $newScript;
    }
}
