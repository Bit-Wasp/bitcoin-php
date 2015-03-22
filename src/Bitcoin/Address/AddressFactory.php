<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Key\KeyInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;

class AddressFactory
{
    /**
     * @param KeyInterface $key
     * @return PayToPubKeyHashAddress
     */
    public static function fromKey(KeyInterface $key)
    {
        $address = new PayToPubKeyHashAddress($key);
        return $address;
    }

    /**
     * @param ScriptInterface $script
     * @return ScriptHashAddress
     */
    public static function fromScript(ScriptInterface $script)
    {
        $address = new ScriptHashAddress($script);
        return $address;
    }
}
