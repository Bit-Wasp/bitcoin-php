<?php

namespace Afk11\Bitcoin;

use Afk11\Bitcoin\Key\KeyInterface;
use Afk11\Bitcoin\Script\ScriptInterface;

class Address
{
    /**
     * @var NetworkInterface
     */
    private $network;

    /**
     * @param NetworkInterface $network
     */
    public function __construct(NetworkInterface $network)
    {
        $this->network = $network;
    }

    /**
     * @param KeyInterface $key
     * @return string
     */
    public function fromKey(KeyInterface $key)
    {
        $pubKeyHash = $key->getPubKeyHash();
        $base58 = Base58::encodeCheck($this->network->getAddressByte() . $pubKeyHash);
        return $base58;
    }

    /**
     * @param ScriptInterface $script
     * @return string
     */
    public function fromScript(ScriptInterface $script)
    {
        $scriptHash = $script->getScriptHash();
        $base58 = Base58::encodeCheck($this->network->getP2shByte() . $scriptHash);
        return $base58;
    }
}