<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\KeyToScript\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptAndSignData;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptDataFactory;

abstract class KeyToScriptDataFactory extends ScriptDataFactory
{
    /**
     * @var PublicKeySerializerInterface
     */
    protected $pubKeySerializer;

    /**
     * KeyToP2PKScriptFactory constructor.
     * @param PublicKeySerializerInterface|null $pubKeySerializer
     */
    public function __construct(PublicKeySerializerInterface $pubKeySerializer = null)
    {
        if (null === $pubKeySerializer) {
            $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true);
        }

        $this->pubKeySerializer = $pubKeySerializer;
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @return ScriptAndSignData
     */
    abstract protected function convertKeyToScriptData(PublicKeyInterface $publicKey): ScriptAndSignData;

    /**
     * @param KeyInterface $key
     * @return ScriptAndSignData
     */
    public function convertKey(KeyInterface $key): ScriptAndSignData
    {
        if ($key->isPrivate()) {
            /** @var PrivateKeyInterface $key */
            $key = $key->getPublicKey();
        }
        /** @var PublicKeyInterface $key */
        return $this->convertKeyToScriptData($key);
    }
}
