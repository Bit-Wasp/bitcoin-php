<?php

namespace BitWasp\Bitcoin\Serializer\Network\Message;

use BitWasp\Bitcoin\Network\Messages\Reject;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class RejectSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->varstring()
            ->uint8()
            ->varstring()
            ->varstring()
            ->getTemplate();
    }

    /**
     * @param Reject $reject
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(Reject $reject)
    {
        return $this->getTemplate()->write([
            $reject->getMessage(),
            $reject->getCode(),
            $reject->getReason(),
            $reject->getData()
        ]);
    }

    /**
     * @param Parser $parser
     * @return array
     */
    public function fromParser(Parser & $parser)
    {
        list ($message, $code, $reason, $data) = $this->getTemplate()->parse($parser);

        return new Reject(
            $message,
            $code,
            $reason,
            $data
        );
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
