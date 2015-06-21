<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Network\Structure\InventoryVector;

abstract class AbstractInventory extends NetworkSerializable implements \Countable
{
    /**
     * @var InventoryVector[]
     */
    private $items = [];

    /**
     * @param InventoryVector[] $vector
     */
    public function __construct(array $vector)
    {
        foreach ($vector as $item) {
            $this->addItem($item);
        }
    }

    /**
     * @param InventoryVector $item
     */
    private function addItem(InventoryVector $item)
    {
        $this->items[] = $item;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @return InventoryVector[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param int $index
     * @return InventoryVector
     */
    public function getItem($index)
    {
        if (false === isset($this->items[$index])) {
            throw new \InvalidArgumentException('No item found at that index');
        }

        return $this->items[$index];
    }
}
