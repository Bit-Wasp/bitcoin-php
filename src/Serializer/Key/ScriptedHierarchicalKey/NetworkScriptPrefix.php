<?php

namespace BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey;

use BitWasp\Bitcoin\Key\KeyToScript\ScriptDataFactory;

class NetworkScriptPrefix
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
