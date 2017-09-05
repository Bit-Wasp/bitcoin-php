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
     * Returns a list of paths for this script. This is not
     * always guaranteed to be in order, so take care that
     * you actually work out the paths in advance of signing,
     * and hard code them somehow.
     *
     * @return array[] - array of paths
     */
    public function getPaths()
    {
        return array_map(function (ScriptBranch $branch) {
            return $branch->getPath();
        }, $this->branchMap);
    }

    /**
     * Look up the branch idx by it's path
     *
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
     * Look up the branch by it's path
     *
     * @param array $branchDesc
     * @return bool|ScriptBranch
     */
    public function getBranchByPath(array $branchDesc)
    {
        $idx = $this->getBranchIdx($branchDesc);
        if (!array_key_exists($idx, $this->branchMap)) {
            throw new \RuntimeException("Coding error, missing entry in branch map for desc");
        }

        return $this->branchMap[$idx];
    }

    /**
     * @param $branch
     * @return ScriptInterface|bool
     */
    public function getMutuallyExclusiveOps($branch)
    {
        if (!($branch = $this->getBranchByPath($branch))) {
            return false;
        }

        $steps = $branch->getSignSteps();
        $ops = [];
        foreach ($steps as $step) {
            $ops = array_merge($ops, $step);
        }

        return ScriptFactory::fromOperations($ops);
    }
}
