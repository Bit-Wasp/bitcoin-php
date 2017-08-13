<?php

namespace BitWasp\Bitcoin\Script\ScriptInfo;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\BufferInterface;

class PayToPubkey
{
    /**
     * @var BufferInterface
     */
    private $publicKey;

    /**
     * @param ScriptInterface $script
     */
    public function __construct(ScriptInterface $script)
    {
        $chunks = $script->getScriptParser()->decode();
        if (count($chunks) !== 2 || !$chunks[0]->isPush() || $chunks[1]->getOp() !== Opcodes::OP_CHECKSIG) {
            throw new \InvalidArgumentException('Malformed pay-to-pubkey script');
        }
        $this->publicKey = $chunks[0]->getData();
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
        return $publicKey->getBuffer()->equals($this->publicKey);
    }

    /**
     * @return BufferInterface[]
     */
    public function getKeyBuffer()
    {
        return $this->publicKey;
    }
}
