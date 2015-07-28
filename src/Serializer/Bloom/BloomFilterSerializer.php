<?php

namespace BitWasp\Bitcoin\Serializer\Bloom;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Bloom\BloomFilter;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class BloomFilterSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->vector(function (Parser & $parser) {
                return $parser->readBytes(1)->getInt();
            })
            ->uint32le()
            ->uint32le()
            ->uint8()
            ->getTemplate();
    }

    /**
     * @param BloomFilter $filter
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(BloomFilter $filter)
    {
        $math = new Math();

        $vBuf = [];
        foreach ($filter->getData() as $i) {
            $hex = $math->decHex($i);
            $vBuf[] = Buffer::hex($hex, 1);
        }

        return $this->getTemplate()->write([
            $vBuf,
            $filter->getNumHashFuncs(),
            $filter->getTweak(),
            $filter->getFlags()->getFlags()
        ]);
    }

    /**
     * @param Parser $parser
     * @return BloomFilter
     */
    public function fromParser(Parser & $parser)
    {
        list ($vData, $numHashFuncs, $nTweak, $flags) = $this->getTemplate()->parse($parser);

        return new BloomFilter(
            Bitcoin::getMath(),
            $vData,
            $numHashFuncs,
            $nTweak,
            new Flags($flags)
        );
    }

    /**
     * @param $data
     * @return BloomFilter
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }
}
