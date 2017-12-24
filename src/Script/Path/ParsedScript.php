<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script\Path;

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
            throw new \RuntimeException("LogicOpNode was not for root");
        }

        $descriptorIdx = 0;
        $keyedIdxMap = []; // descriptor => ScriptBranch
        foreach ($branches as $branch) {
            $descriptor = $branch->getPath();
            $descriptorKey = json_encode($descriptor);
            if (array_key_exists($descriptorKey, $keyedIdxMap)) {
                throw new \RuntimeException("Duplicate logical pathway, invalid ScriptBranch found");
            }

            $keyedIdxMap[$descriptorKey] = $branch;
            $descriptorIdx++;
        }

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
        }, $this->descriptorMap);
    }

    /**
     * Look up the branch by it's path
     *
     * @param array $branchDesc
     * @return bool|ScriptBranch
     */
    public function getBranchByPath(array $branchDesc)
    {
        $key = json_encode($branchDesc);
        if (!array_key_exists($key, $this->descriptorMap)) {
            throw new \RuntimeException("Unknown logical pathway");
        }

        return $this->descriptorMap[$key];
    }
}
