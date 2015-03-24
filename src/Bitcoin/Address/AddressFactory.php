<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\KeyInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;
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
     * @param                  $address
     * @param NetworkInterface $network
     * @return AddressInterface
     * @throws \BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
     */
    public static function fromString($address, NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();

        $data = Base58::decodeCheck($address);

        $prefixByte = substr($data, 0, 2);

        if ($prefixByte === $network->getP2shByte()) {
            return new ScriptHashAddress(substr($data, 2));
        } else if ($prefixByte === $network->getAddressByte()) {
            return new PayToPubKeyHashAddress(substr($data, 2));
        } else {
            throw new \InvalidArgumentException("Invalid prefix [{$prefixByte}]");
        }
    }
}
