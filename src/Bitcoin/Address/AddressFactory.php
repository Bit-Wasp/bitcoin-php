<?php

namespace Afk11\Bitcoin\Address;

use Afk11\Bitcoin\Base58;
use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Key\KeyInterface;
use Afk11\Bitcoin\Network\NetworkInterface;
use Afk11\Bitcoin\Script\ScriptInterface;

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
     * @return ScriptHashAddress
     * @throws \Afk11\Bitcoin\Exceptions\Base58ChecksumFailure
     */
    public static function fromString($address, NetworkInterface $network = null) {
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
