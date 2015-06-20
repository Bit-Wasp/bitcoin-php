<?php

namespace BitWasp\Bitcoin\Serializer\Network\Structure;

use BitWasp\Bitcoin\Network\Structure\InventoryVector;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class InventoryVectorSerializer
{
    /**
     * @return mixed
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->uint32le()
            ->bytestring(32)
            ->getTemplate();
    }

    /**
     * @param InventoryVector $inv
     * @return mixed
     */
    public function serialize(InventoryVector $inv)
    {
        return $this->getTemplate()->write([
            $inv->getType(),
            $inv->getHash()
        ]);
    }

    /**
     * @param Parser $parser
     * @return mixed
     */
    public function fromParser(Parser & $parser)
    {
        list($type, $hash) = $this->getTemplate()->parse($parser);
        return new InventoryVector(
            $type,
            $hash
        );
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }
}
