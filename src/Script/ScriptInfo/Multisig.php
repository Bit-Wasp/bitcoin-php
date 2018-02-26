<?php

namespace BitWasp\Bitcoin\Script\ScriptInfo;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\BufferInterface;

class Multisig
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
     * @var BufferInterface[]
     */
    private $keyBuffers = [];

    /**
     * @var PublicKeySerializerInterface
     */
    private $pubKeySerializer;

    /**
     * Multisig constructor.
     * @param ScriptInterface $script
     * @param PublicKeySerializerInterface|null $pubKeySerializer
     */
    public function __construct(ScriptInterface $script, PublicKeySerializerInterface $pubKeySerializer = null)
    {
        if (null === $pubKeySerializer) {
            $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, Bitcoin::getEcAdapter());
        }

        $parse = $script->getScriptParser()->decode();
        if (count($parse) < 4 || end($parse)->getOp() !== Opcodes::OP_CHECKMULTISIG) {
            throw new \InvalidArgumentException('Malformed multisig script');
        }

        $mCode = $parse[0]->getOp();
        $nCode = $parse[count($parse) - 2]->getOp();

        $this->m = \BitWasp\Bitcoin\Script\decodeOpN($mCode);
        $publicKeyBuffers = [];
        foreach (array_slice($parse, 1, -2) as $key) {
            /** @var \BitWasp\Bitcoin\Script\Parser\Operation $key */
            if (!$key->isPush()) {
                throw new \RuntimeException('Malformed multisig script');
            }

            $buffer = $key->getData();
            $publicKeyBuffers[] = $buffer;
        }

        $this->n = \BitWasp\Bitcoin\Script\decodeOpN($nCode);
        if ($this->n === 0 || $this->n !== count($publicKeyBuffers)) {
            throw new \LogicException('No public keys found in script');
        }
        $this->keyBuffers = $publicKeyBuffers;
        $this->pubKeySerializer = $pubKeySerializer;
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
     * @param PublicKeyInterface $publicKey
     * @return bool
     */
    public function checkInvolvesKey(PublicKeyInterface $publicKey)
    {
        $buffer = $this->pubKeySerializer->serialize($publicKey);
        foreach ($this->keyBuffers as $key) {
            if ($key->equals($buffer)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array|BufferInterface[]
     */
    public function getKeyBuffers()
    {
        return $this->keyBuffers;
    }
}
