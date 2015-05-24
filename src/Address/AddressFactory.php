<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\KeyInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptInterface;

use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Buffertools\Buffer;

class AddressFactory
{
    /**
     * @param KeyInterface $key
     * @return PayToPubKeyHashAddress
     */
    public static function fromKey(KeyInterface $key)
    {
        $address = new PayToPubKeyHashAddress($key->getPubKeyHash());
        return $address;
    }

    /**
     * @param ScriptInterface $script
     * @return ScriptHashAddress
     */
    public static function fromScript(ScriptInterface $script)
    {
        $address = new ScriptHashAddress($script->getScriptHash());
        return $address;
    }

    /**
     * @param ScriptInterface $outputScript
     * @return PayToPubKeyHashAddress|ScriptHashAddress
     */
    public static function fromOutputScript(ScriptInterface $outputScript)
    {
        $classifier = new OutputClassifier($outputScript);
        $type = $classifier->classify();
        $parsed = $outputScript->getScriptParser()->parse();

        if ($type == OutputClassifier::PAYTOPUBKEYHASH) {
            /** @var \BitWasp\Buffertools\Buffer $hash */
            $hash = $parsed[2];
            return new PayToPubKeyHashAddress($hash);
        } else if ($type == OutputClassifier::PAYTOSCRIPTHASH) {
            /** @var \BitWasp\Buffertools\Buffer $hash */
            $hash = $parsed[1];
            return new ScriptHashAddress($hash);
        }

        throw new \RuntimeException('Script type is not associated with an address');
    }

    /**
     * @param string $address
     * @param NetworkInterface $network
     * @return AddressInterface
     * @throws \BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
     */
    public static function fromString($address, NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        $data = Base58::decodeCheck($address);
        $prefixByte = $data->slice(0, 1)->getHex();

        if ($prefixByte === $network->getP2shByte()) {
            return new ScriptHashAddress($data->slice(1));
        } else if ($prefixByte === $network->getAddressByte()) {
            return new PayToPubKeyHashAddress($data->slice(1));
        } else {
            throw new \InvalidArgumentException("Invalid prefix [{$prefixByte}]");
        }
    }

    /**
     * @param ScriptInterface $script
     * @param NetworkInterface $network
     * @return String
     * @throws \RuntimeException
     */
    public static function getAssociatedAddress(ScriptInterface $script, NetworkInterface $network = null)
    {
        $classifier = new OutputClassifier($script);
        $network = $network ?: Bitcoin::getNetwork();
        try {
            $address = $classifier->isPayToPublicKey()
                ? PublicKeyFactory::fromHex($script->getScriptParser()->parse()[0]->getHex())->getAddress()
                : self::fromOutputScript($script);
            return Base58::encodeCheck(Buffer::hex($network->getAddressByte() . $address->getHash()));
        } catch (\Exception $e) {
            throw new \RuntimeException('No address associated with this script type');
        }
    }
}
