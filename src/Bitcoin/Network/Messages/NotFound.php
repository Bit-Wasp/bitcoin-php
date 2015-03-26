<?php

namespace BitWasp\Bitcoin\Network\Messages;


use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Network\Structure\InventoryVector;
use BitWasp\Bitcoin\Parser;

class NotFound extends NetworkSerializable
{
    /**
     * @var InventoryVector[]
     */
    private $vectors = [];

    /**
     * @param InventoryVector[] $vectors
     */
    public function __construct(array $vectors = [])
    {
        foreach ($vectors as $vector) {
            if ($vector instanceof InventoryVector) {
                $this->addItem($vector);
            }
        }
    }

    /**
     * @return string
     */
    public function getNetworkCommand()
    {
        return 'notfound';
    }

    /**
     * @param InventoryVector $vector
     * @return $this
     */
    public function addItem(InventoryVector $vector)
    {
        $this->vectors[] = $vector;
        return $this;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        $parser = new Parser();
        $parser->writeArray($this->vectors);
        return $parser->getBuffer();
    }
}