<?php

namespace BitWasp\Bitcoin\Serializer\Network\Message;

use BitWasp\Bitcoin\Network\Messages\Inv;
use BitWasp\Bitcoin\Serializer\Network\Structure\InventoryVectorSerializer;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class InvSerializer
{
    /**
     * @var InventoryVectorSerializer
     */
    private $invVector;

    /**
     * @param InventoryVectorSerializer $invVector
     */
    public function __construct(InventoryVectorSerializer $invVector)
    {
        $this->invVector = $invVector;
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->vector(function (Parser & $parser) {
                return $this->invVector->fromParser($parser);
            })
            ->getTemplate();
    }

    /**
     * @param Parser $parser
     * @return array
     */
    public function fromParser(Parser & $parser)
    {
        list ($items) = $this->getTemplate()->parse($parser);
        return new Inv($items);
    }

    /**
     * @param $data
     * @return array
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param Inv $inv
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(Inv $inv)
    {
        return $this->getTemplate()->write([
            $inv->getItems()
        ]);
    }
}
