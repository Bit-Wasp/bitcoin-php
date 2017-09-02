<?php

namespace BitWasp\Bitcoin\Script\Path;

use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;

class ParsedScript
{
    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @var LogicOpNode
     */
    private $ast;

    /**
     * @var array
     */
    private $branches;

    /**
     * @var array
     */
    private $branchMap;

    /**
     * @var array
     */
    private $descriptorMap;

    /**
     * ParsedScript constructor.
     * @param ScriptInterface $script
     * @param LogicOpNode $ast
     * @param ScriptBranch[] $branches
     */
    public function __construct(ScriptInterface $script, LogicOpNode $ast, array $branches)
    {
        if (!$ast->isRoot()) {
            throw new \RuntimeException("AST is for invalid node, wasn't root");
        }

        $descriptorIdx = 0;
        $keyedBranchMap = []; // idx => ScriptBranch
        $keyedIdxMap = []; // descriptor => ScriptBranch
        foreach ($branches as $branch) {
            $descriptor = $branch->getPath();
            $descriptorKey = json_encode($descriptor);
            if (array_key_exists($descriptorKey, $keyedBranchMap)) {
                throw new \RuntimeException("Duplicate branch descriptor, invalid ScriptBranch found");
            }

            $keyedBranchMap[] = $branch;
            $keyedIdxMap[$descriptorKey] = $descriptorIdx;
            $descriptorIdx++;
        }

        $this->branchMap = $keyedBranchMap;
        $this->descriptorMap = $keyedIdxMap;
        $this->script = $script;
        $this->ast = $ast;
    }

    /**
     * @return bool
     */
    public function hasMultipleBranches()
    {
        return $this->ast->hasChildren();
    }

    /**
     * @param array $branchDesc
     * @return int
     */
    private function getBranchIdx(array $branchDesc)
    {
        $descriptorKey = json_encode($branchDesc);

        if (array_key_exists($descriptorKey, $this->descriptorMap)) {
            return $this->descriptorMap[$descriptorKey];
        }

        throw new \RuntimeException("Unknown branch");
    }

    /**
     * @param array $branchDesc
     * @return bool|ScriptBranch
     */
    public function getBranchByDesc(array $branchDesc)
    {
        $idx = $this->getBranchIdx($branchDesc);
        if (!array_key_exists($idx, $this->branchMap)) {
            throw new \RuntimeException("Coding error, missing entry in branch map for desc");
        }

        return $this->branchMap[$idx];
    }

    /**
     * @param int $idx
     * @return bool|ScriptBranch
     */
    public function getBranchByIdx($idx)
    {
        if (array_key_exists($idx, $this->branchMap)) {
            return $this->branchMap[$idx];
        }

        throw new \RuntimeException("Unknown branch index");
    }

    /**
     * @param $branch
     * @return ScriptInterface|bool
     */
    public function getMutuallyExclusiveOps($branch)
    {
        if (!($branch = $this->getBranchByDesc($branch))) {
            return false;
        }

        $steps = $branch->getSignSteps();
        $ops = [];
        foreach ($steps as $step) {
            $ops = array_merge($ops, $step->all());
        }

        return ScriptFactory::fromOperations($ops);
    }
}
