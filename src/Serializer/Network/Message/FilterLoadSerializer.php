<?php

namespace BitWasp\Bitcoin\Serializer\Network\Message;


use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Network\Messages\FilterLoad;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class FilterLoadSerializer
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
            ->uint32le()
            ->uint32le()
            ->uint8()
            ->getTemplate();
    }

    /**
     * @param Parser $parser
     * @return FilterLoad
     */
    public function fromParser(Parser & $parser)
    {
        list ($vFilter, $nHashFuncs, $nTweak, $nFlags) = $this->getTemplate()->parse($parser);

        return new FilterLoad(
            $vFilter,
            $nHashFuncs,
            $nTweak,
            new Flags($nFlags)
        );
    }

    /**
     * @param $data
     * @return FilterLoad
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param FilterLoad $filterload
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(FilterLoad $filterload)
    {
        return $this->getTemplate()->write([
            $filterload->getFilter(),
            $filterload->getNumHashFuncs(),
            $filterload->getTweak(),
            $filterload->getFlags()->getFlags()
        ]);
    }
}