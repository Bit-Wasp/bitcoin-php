<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\KeyToScript\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
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
     * @param PublicKeyInterface ...$publicKeys
     * @return ScriptAndSignData
     */
    abstract protected function convertKeyToScriptData(PublicKeyInterface... $publicKeys): ScriptAndSignData;

    /**
     * @param KeyInterface ...$keys
     * @return ScriptAndSignData
     */
    public function convertKey(KeyInterface... $keys): ScriptAndSignData
    {
        /** @var PublicKeyInterface[] $pubs */
        $pubs = [];
        foreach ($keys as $key) {
            if ($key instanceof PrivateKeyInterface) {
                $key = $key->getPublicKey();
            }
            $pubs[] = $key;
        }

        return $this->convertKeyToScriptData(...$pubs);
    }
}
