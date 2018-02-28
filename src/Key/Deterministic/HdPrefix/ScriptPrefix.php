<?php

namespace BitWasp\Bitcoin\Key\Deterministic\HdPrefix;

use BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptDataFactory;

class ScriptPrefix
{
    /**
     * @var string
     */
    private $privatePrefix;

    /**
     * @var string
     */
    private $publicPrefix;

    /**
     * @var ScriptDataFactory
     */
    private $scriptDataFactory;

    /**
     * ScriptPrefixConfig constructor.
     * @param ScriptDataFactory $scriptDataFactory
     * @param string $privatePrefix
     * @param string $publicPrefix
     */
    public function __construct(ScriptDataFactory $scriptDataFactory, $privatePrefix, $publicPrefix)
    {
        if (strlen($privatePrefix) !== 8) {
            throw new InvalidNetworkParameter("Invalid HD private prefix: wrong length");
        }

        if (!ctype_xdigit($privatePrefix)) {
            throw new InvalidNetworkParameter("Invalid HD private prefix: expecting hex");
        }

        if (strlen($publicPrefix) !== 8) {
            throw new InvalidNetworkParameter("Invalid HD public prefix: wrong length");
        }

        if (!ctype_xdigit($publicPrefix)) {
            throw new InvalidNetworkParameter("Invalid HD public prefix: expecting hex");
        }

        $this->scriptDataFactory = $scriptDataFactory;
        $this->publicPrefix = $publicPrefix;
        $this->privatePrefix = $privatePrefix;
    }

    /**
     * @return string
     */
    public function getPrivatePrefix()
    {
        return $this->privatePrefix;
    }

    /**
     * @return string
     */
    public function getPublicPrefix()
    {
        return $this->publicPrefix;
    }

    /**
     * @return ScriptDataFactory
     */
    public function getScriptDataFactory()
    {
        return $this->scriptDataFactory;
    }
}
