<?php

namespace BitWasp\Bitcoin\Script\ScriptInfo;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\BufferInterface;

class PayToPubkeyHash implements ScriptInfoInterface
{

    /**
     * @var BufferInterface
     */
    private $hash;

    /**
     * @param ScriptInterface $script
     */
    public function __construct(ScriptInterface $script)
    {
        $chunks = $script->getScriptParser()->decode();
        if (count($chunks) !== 5 || !$chunks[2]->isPush() || !$chunks[2]->getData() === 20) {
            throw new \RuntimeException('Malformed pay-to-pubkey-hash script');
        }

        /** @var BufferInterface $hash */
        $hash = $chunks[2]->getData();
        $this->hash = $hash;
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
}
