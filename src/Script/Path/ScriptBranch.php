<?php

namespace BitWasp\Bitcoin\Script\Path;

use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\ScriptFactory;
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
     * @return ScriptInterface
     */
    public function getFullScript()
    {
        return $this->fullScript;
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
        foreach ($this->scriptSections as $segment) {
            $sequence = array_merge($sequence, $segment);
        }
        return $sequence;
    }

    /**
     * @return ScriptInterface
     */
    public function getNeuteredScript()
    {
        return ScriptFactory::fromOperations($this->getOps());
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $m = [];
        foreach ($this->scriptSections as $segment) {
            $m[] = ScriptFactory::fromOperations($segment->all());
        }

        $path = [];
        foreach ($this->branch as $flag) {
            $path[] = $flag ? 'true' : 'false';
        }

        return [
            'branch' => implode(", ", $path),
            'segments' => $m,
        ];
    }

    /**
     * @return array[]
     */
    public function getSignSteps()
    {
        $steps = [];
        foreach ($this->scriptSections as $segment) {
            /** @var Operation[] $segment */
            if (!(count($segment) === 1 && $segment[1]->isLogical())) {
                $steps[] = $segment;
            }
        }

        return $steps;
    }
}
