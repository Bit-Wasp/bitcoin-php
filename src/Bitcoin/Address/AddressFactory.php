<?php

namespace Afk11\Bitcoin\Address;

use Afk11\Bitcoin\Key\KeyInterface;
use Afk11\Bitcoin\NetworkInterface;
use Afk11\Bitcoin\Script\ScriptInterface;

class AddressFactory
{
    /**
     * @param NetworkInterface $network
     * @param KeyInterface $key
     * @return PayToPubKeyHashAddress
     */
    public static function fromKey(NetworkInterface $network, KeyInterface $key)
    {
        $address = new PayToPubKeyHashAddress($network, $key);
        return $address;
    }

    /**
     * @param NetworkInterface $network
     * @param ScriptInterface $script
     * @return ScriptHashAddress
     */
    public static function fromScript(NetworkInterface $network, ScriptInterface $script)
    {
        $address = new ScriptHashAddress($network, $script);
        return $address;
    }
}
