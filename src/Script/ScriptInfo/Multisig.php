<?php

namespace BitWasp\Bitcoin\Script\ScriptInfo;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffer;

class Multisig implements ScriptInfoInterface
{
    /**
     * @var int
     */
    private $m;

    /**
     * @var int
     */
    private $n;

    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @var PublicKeyInterface[]
     */
    private $keys = [];

    /**
     * @param ScriptInterface $script
     */
    public function __construct(ScriptInterface $script)
    {
        $publicKeys = [];
        $parse = $script->getScriptParser()->parse();
        $opCodes = $script->getOpcodes();
        $this->m = $opCodes->getOpByName($parse[0]) - $opCodes->getOpByName('OP_1') + 1 ;
        foreach (array_slice($parse, 1, -2) as $item) {
            if (!$item instanceof Buffer) {
                throw new \RuntimeException('Unable to load public key');
            }
            $publicKeys[] = PublicKeyFactory::fromHex($item);
        }

        $this->n = count($publicKeys);
        if ($this->n === 0) {
            throw new \LogicException('No public keys found in script');
        }

        $this->script = $script;
        $this->keys = $publicKeys;
    }

    /**
     * @return int
     */
    public function getRequiredSigCount()
    {
        return $this->m;
    }

    /**
     * @return int
     */
    public function getKeyCount()
    {
        return $this->n;
    }

    /**
     * @return string
     */
    public function classification()
    {
        return OutputClassifier::MULTISIG;
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @return bool
     */
    public function checkInvolvesKey(PublicKeyInterface $publicKey)
    {
        $binary = $publicKey->getBinary();
        foreach ($this->keys as $key) {
            if ($key->getBinary() === $binary) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface[]
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * @param array $signatures
     * @param array $publicKeys
     * @return Script|ScriptInterface
     */
    public function makeScriptSig(array $signatures = [], array $publicKeys = [])
    {
        $newScript = new Script();
        if (count($signatures) > 0) {
            $newScript = ScriptFactory::scriptSig()->multisig($signatures);
        }

        return $newScript;
    }
}
