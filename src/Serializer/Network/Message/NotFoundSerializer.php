<?php

namespace BitWasp\Bitcoin\Serializer\Network\Message;

use BitWasp\Bitcoin\Network\Messages\NotFound;
use BitWasp\Bitcoin\Serializer\Network\Structure\InventoryVectorSerializer;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class NotFoundSerializer
{
    /**
     * @var InventoryVectorSerializer
     */
    private $invSerializer;

    /**
     * @param InventoryVectorSerializer $inv
     */
    public function __construct(InventoryVectorSerializer $inv)
    {
        $this->invSerializer = $inv;
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->vector(function (Parser & $parser) {
                return $this->invSerializer->fromParser($parser);
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
        return new NotFound($items);
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
     * @param NotFound $notFound
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(NotFound $notFound)
    {
        return $this->getTemplate()->write([
            $notFound->getItems()
        ]);
    }
}
