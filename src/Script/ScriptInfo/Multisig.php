<?php

namespace BitWasp\Bitcoin\Script\ScriptInfo;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;

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
        $parse = $script->getScriptParser()->decode();
        if (count($parse) < 4) {
            throw new \InvalidArgumentException('Malformed multisig script');
        }

        $mCode = $parse[0]->getOp();
        $nCode = $parse[count($parse) - 2]->getOp();

        $this->m = (int) \BitWasp\Bitcoin\Script\decodeOpN($mCode);
        foreach (array_slice($parse, 1, -2) as $key) {
            /** @var \BitWasp\Bitcoin\Script\Parser\Operation $key */
            if (!$key->isPush()) {
                throw new \RuntimeException('Malformed multisig script');
            }

            $publicKeys[] = PublicKeyFactory::fromHex($key->getData());
        }

        $n = \BitWasp\Bitcoin\Script\decodeOpN($nCode);
        $this->n = count($publicKeys);
        if ($this->n === 0 || $this->n !== $n) {
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
     * @param TransactionSignatureInterface[] $signatures
     * @param PublicKeyInterface[] $publicKeys
     * @return Script|ScriptInterface
     */
    public function makeScriptSig(array $signatures = [], array $publicKeys = [])
    {
        $newScript = new Script();
        if (count($signatures) > 0) {
            $sequence = [Opcodes::OP_0];
            foreach ($signatures as $sig) {
                $sequence[] = $sig->getBuffer();
            }

            $newScript = ScriptFactory::sequence($sequence);
        }

        return $newScript;
    }
}
