<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;

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
