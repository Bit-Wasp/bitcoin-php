<?php

namespace BitWasp\Bitcoin\Key\KeyToScript\Decorator;

use BitWasp\Bitcoin\Exceptions\DisallowedScriptDataFactoryException;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\KeyToScriptDataFactory;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptDataFactory;

abstract class ScriptHashDecorator extends ScriptDataFactory
{
    /**
     * @var KeyToScriptDataFactory
     */
    protected $scriptDataFactory;

    /**
     * @var string[]
     */
    protected $allowedScriptTypes = [];

    /**
     * @var string
     */
    protected $decorateType;

    /**
     * ScriptHashDecorator constructor.
     * @param KeyToScriptDataFactory $scriptDataFactory
     * @throws DisallowedScriptDataFactoryException
     */
    public function __construct(KeyToScriptDataFactory $scriptDataFactory)
    {
        if (!in_array($scriptDataFactory->getScriptType(), $this->allowedScriptTypes, true)) {
            throw new DisallowedScriptDataFactoryException("Unsupported key-to-script factory for this script-hash type.");
        }
        $this->scriptDataFactory = $scriptDataFactory;
    }

    /**
     * @return string
     */
    public function getScriptType()
    {
        return sprintf("%s|%s", $this->decorateType, $this->scriptDataFactory->getScriptType());
    }
}
