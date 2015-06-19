<?php

namespace BitWasp\Bitcoin\Serializer\Network\Message;

use BitWasp\Bitcoin\Network\Messages\Ping;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class PingSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->uint64()
            ->getTemplate();
    }

    /**
     * @param Ping $ping
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(Ping $ping)
    {
        return $this->getTemplate()->write([
            $ping->getNonce()
        ]);
    }

    /**
     * @param Parser $parser
     * @return array
     */
    public function fromParser(Parser & $parser)
    {
        list($nonce) = $this->getTemplate()->parse($parser);
        return new Ping($nonce);
    }

    /**
     * @param $data
     * @return array
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }
}
