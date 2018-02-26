<?php

namespace BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey;

use BitWasp\Bitcoin\Key\KeyToScript\ScriptDataFactoryInterface;

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
     * @var ScriptDataFactoryInterface
     */
    private $scriptDataFactory;

    /**
     * ScriptPrefixConfig constructor.
     * @param ScriptDataFactoryInterface $scriptDataFactory
     * @param string $privatePrefix
     * @param string $publicPrefix
     */
    public function __construct(ScriptDataFactoryInterface $scriptDataFactory, $privatePrefix, $publicPrefix)
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
     * @return ScriptDataFactoryInterface
     */
    public function getScriptDataFactory()
    {
        return $this->scriptDataFactory;
    }
}
