<?php

namespace BitWasp\Bitcoin\Serializer\Network\Message;

use BitWasp\Bitcoin\Network\Messages\FilterAdd;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class FilterAddSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->vector(function (Parser & $parser) {
                return $parser->readBytes(1, true);
            })
            ->getTemplate();
    }

    /**
     * @param Parser $parser
     * @return FilterAdd
     */
    public function fromParser(Parser & $parser)
    {
        list ($vFilter) = $this->getTemplate()->parse($parser);

        return new FilterAdd($vFilter);
    }

    /**
     * @param $data
     * @return FilterAdd
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param FilterAdd $filteradd
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(FilterAdd $filteradd)
    {
        return $this->getTemplate()->write([
            $filteradd->getFilter()
        ]);
    }
}
