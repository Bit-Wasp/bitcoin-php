<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script\Path;

class LogicOpNode
{
    /**
     * @var LogicOpNode|null
     */
    private $parent;

    /**
     * @var bool
     */
    private $value;

    /**
     * @var LogicOpNode[]
     */
    private $children = [];

    /**
     * MASTNode constructor.
     * @param self|null $parent
     * @param bool|null $value
     */
    public function __construct(self $parent = null, $value = null)
    {
        $this->parent = $parent;
        $this->value = $value;
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
            if ($value === null) {
                return [[]];
            }
            return [$value];
        }
    }

    /**
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->parent == null;
    }

    /**
     * @return bool
     */
    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    /**
     * @return LogicOpNode|null
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
     * @return LogicOpNode
     */
    public function getChild($value)
    {
        if (!isset($this->children[$value])) {
            throw new \RuntimeException("Child not found");
        }
        return $this->children[$value];
    }

    /**
     * @return array
     */
    public function split(): array
    {
        if (count($this->children) > 0) {
            throw new \RuntimeException("Sanity check - don't split twice");
        }

        $children = [new LogicOpNode($this, false), new LogicOpNode($this, true)];
        foreach ($children as $child) {
            $this->children[] = $child;
        }
        return $children;
    }
}
