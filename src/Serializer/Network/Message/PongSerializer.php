<?php

namespace BitWasp\Bitcoin\Serializer\Network\Message;

use BitWasp\Bitcoin\Network\Messages\Pong;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class PongSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    private function getTemplate()
    {
        return (new TemplateFactory())
            ->uint64()
            ->getTemplate();
    }

    /**
     * @param Pong $pong
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(Pong $pong)
    {
        return $this->getTemplate()->write([
            $pong->getNonce()
        ]);
    }

    /**
     * @param Parser $parser
     * @return array
     */
    public function fromParser(Parser & $parser)
    {
        list($nonce) = $this->getTemplate()->parse($parser);
        return new Pong($nonce);
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
