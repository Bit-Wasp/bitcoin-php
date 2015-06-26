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
            ->varstring()
            ->getTemplate();
    }

    /**
     * @param Parser $parser
     * @return FilterAdd
     */
    public function fromParser(Parser & $parser)
    {
        list ($data) = $this->getTemplate()->parse($parser);

        return new FilterAdd($data);
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
            $filteradd->getData()
        ]);
    }
}
