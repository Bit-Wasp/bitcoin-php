<?php

namespace BitWasp\Bitcoin\Script\ScriptInfo;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffer;

class PayToPubkeyHash implements ScriptInfoInterface
{
    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @var \BitWasp\Buffertools\Buffer
     */
    private $hash;

    /**
     * @param ScriptInterface $script
     */
    public function __construct(ScriptInterface $script)
    {
        $this->script = $script;
        $chunks = $this->script->getScriptParser()->parse();
        if (count($chunks) < 5 || !$chunks[2] instanceof Buffer) {
            throw new \RuntimeException('Malformed pay-to-pubkey-hash script');
        }

        /** @var Buffer $hash */
        $hash = $chunks[2];
        $this->hash = $hash;
    }

    /**
     * @return string
     */
    public function classification()
    {
        return OutputClassifier::PAYTOPUBKEYHASH;
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
        return $publicKey->getPubKeyHash()->getBinary() === $this->hash->getBinary();
    }

    /**
     * @return PublicKeyInterface[]
     */
    public function getKeys()
    {
        return [];
    }

    /**
     * @param array $signatures
     * @param array $publicKeys
     * @return Script|ScriptInterface
     */
    public function makeScriptSig(array $signatures = [], array $publicKeys = [])
    {
        $newScript = new Script();
        if (count($signatures) === $this->getRequiredSigCount() && count($publicKeys) > 0) {
            $newScript = ScriptFactory::scriptSig()->payToPubKeyHash($signatures[0], $publicKeys[0]);
        }

        return $newScript;
    }
}
