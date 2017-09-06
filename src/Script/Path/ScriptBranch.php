<?php

namespace BitWasp\Bitcoin\Script\Path;

use BitWasp\Bitcoin\Script\ScriptInterface;

class ScriptBranch
{
    /**
     * @var ScriptInterface
     */
    private $fullScript;

    /**
     * @var array|\array[]
     */
    private $scriptSections;

    /**
     * @var array|\bool[]
     */
    private $branch;

    /**
     * ScriptBranch constructor.
     * @param ScriptInterface $fullScript
     * @param array $logicalPath
     * @param array $scriptSections
     */
    public function __construct(ScriptInterface $fullScript, array $logicalPath, array $scriptSections)
    {
        $this->fullScript = $fullScript;
        $this->branch = $logicalPath;
        $this->scriptSections = $scriptSections;
    }

    /**
     * @return array|\bool[]
     */
    public function getPath()
    {
        return $this->branch;
    }

    /**
     * @return array|\array[]
     */
    public function getScriptSections()
    {
        return $this->scriptSections;
    }

    /**
     * @return array
     */
    public function getOps()
    {
        $sequence = [];
        foreach ($this->getScriptSections() as $segment) {
            $sequence = array_merge($sequence, $segment);
        }
        return $sequence;
    }
}
