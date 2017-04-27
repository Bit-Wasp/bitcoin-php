<?php
namespace BitWasp\Bitcoin\Script\Path;

use BitWasp\Bitcoin\Script\Interpreter\Stack;
use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffer;

class MASTNode
{

    /**
     * @var MASTNode|null
     */
    private $parent;

    /**
     * @var bool
     */
    private $value;

    /**
     * @var MASTNode[]
     */
    private $children = [];

    private $operations = [];

    /**
     * MASTNode constructor.
     * @param MASTNode|null $parent
     * @param bool|null $value
     */
    public function __construct(MASTNode $parent = null, $value = null)
    {
        $this->parent = $parent;
        $this->value = $value;
    }

    public function assign($fExec, Operation $op) {
        $this->operations[] = [$fExec, $op];
    }

    /**
     * @return array
     */
    public function flags()
    {
        if (count($this->children) > 0) {
            $values = [];
            foreach ($this->children as $k => $child) {
                $flags = $child->flags();
                foreach ($flags as $branch) {
                    $values[] = array_merge($this->isRoot() ? [] : [$this->value], is_array($branch) ? $branch : [$branch]);
                }
            }

            return $values;
        } else {
            $value = $this->value;
            return [$value];
        }
    }

    /**
     * @return Stack[]
     */
    public function stacks()
    {
        $vchFalse = new Buffer("", 0);
        $vchTrue = new Buffer("\x01", 1);
        $stacks = [];
        foreach ($this->flags() as $flagSet) {
            $stack = new Stack();
            foreach (array_reverse($flagSet) as $item) {
                $stack->push($item ? $vchTrue : $vchFalse);
            }
            $stacks[] = $stack;
        }
        return $stacks;
    }

    /**
     * @return bool
     */
    public function isRoot()
    {
        return $this->parent == null;
    }

    /**
     * @return MASTNode|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return bool|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param $value
     * @return MASTNode
     */
    public function getChild($value)
    {
        if (!isset($this->children[$value])) {
            throw new \RuntimeException();
        }
        return $this->children[$value];
    }


    /**
     * @return array
     */
    public function split()
    {
        if (count($this->children) > 0) {
            throw new \RuntimeException("santity - dont split twice");
        }
        $children = [new MASTNode($this, false), new MASTNode($this, true)];
        foreach ($children as $child) {
            $this->children[] = $child;
        }
        return $children;
    }

    public function codes() {
        return $this->operations;
    }

    public function getNodesInPath(array $path) {
        $current = $this;
        $nodes = [$current];
        foreach ($path as $node) {
            $current = $current->getChild($node);
            $nodes[] = $current;
        }
        return $nodes;
    }

    public function getPaths() {
        $paths = [];
        foreach ($this->flags() as $flags) {
            $nodes = $this->getNodes($flags);
            print_r($nodes);
        }
        return $paths;
    }
}
