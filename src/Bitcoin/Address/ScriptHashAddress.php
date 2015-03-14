<?php

namespace Afk11\Bitcoin\Address;

use Afk11\Bitcoin\Network\NetworkInterface;
use Afk11\Bitcoin\Script\ScriptInterface;

class ScriptHashAddress extends Address
{
    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @param ScriptInterface $script
     */
    public function __construct(ScriptInterface $script)
    {
        $this->script = $script;
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function getPrefixByte(NetworkInterface $network)
    {
        return $network->getP2shByte();
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->script->getScriptHash();
    }
}
