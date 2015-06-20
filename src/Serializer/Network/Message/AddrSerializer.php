<?php

namespace BitWasp\Bitcoin\Serializer\Network\Message;

use BitWasp\Bitcoin\Network\Messages\Addr;
use BitWasp\Bitcoin\Serializer\Network\Structure\NetworkAddressTimestampSerializer;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class AddrSerializer
{
    /**
     * @var NetworkAddressTimestampSerializer
     */
    private $netAddr;

    /**
     * @param NetworkAddressTimestampSerializer $serializer
     */
    public function __construct(NetworkAddressTimestampSerializer $serializer)
    {
        $this->netAddr = $serializer;
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->vector(function (Parser & $parser) {
                return $this->netAddr->fromParser($parser);
            })
            ->getTemplate();
    }

    /**
     * @param Parser $parser
     * @return Addr
     */
    public function fromParser(Parser & $parser)
    {
        list ($addresses) = $this->getTemplate()->parse($parser);
        return new Addr($addresses);
    }

    /**
     * @param $data
     * @return Addr
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param Addr $addr
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(Addr $addr)
    {
        return $this->getTemplate()->write([
            $addr->getAddresses()
        ]);
    }
}
