<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\KeyInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptInterface;

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
        } else if ($type == OutputClassifier::PAYTOPUBKEY) {
            /** @var \BitWasp\Buffertools\Buffer $hash */
            $hash = $parsed[0];
            return new PayToPubKeyAddress($hash);
        }

        throw new \RuntimeException('Script type '.$type.' is not associated with an address');
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
}
