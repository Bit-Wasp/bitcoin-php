<?php

namespace Afk11\Bitcoin\Address;

use Afk11\Bitcoin\NetworkInterface;
use Afk11\Bitcoin\Key\KeyInterface;
use Afk11\Bitcoin\Script\ScriptInterface;

class ScriptHashAddress extends Address
{
    /**
     * @var NetworkInterface
     */
    private $network;

    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @param NetworkInterface $network
     * @param KeyInterface $key
     */
    public function __construct(NetworkInterface $network, ScriptInterface $script)
    {
        $this->network = $network;
        $this->script = $script;
    }

    /**
     * @return string
     */
    public function getPrefixByte()
    {
        return $this->network->getP2shByte();
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->script->getScriptHash();
    }
}
