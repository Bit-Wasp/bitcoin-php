<?php

namespace BitWasp\Bitcoin\Serializer\Bloom;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Bloom\BloomFilter;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Template;

class BloomFilterSerializer
{
    /**
     * @var Template
     */
    private $template;

    public function __construct()
    {
        $this->uint32le = Types::uint32le();
        $this->uint8le = Types::uint8le();
        $this->template = new Template([
            Types::vector(function (Parser $parser) {
                return $parser->readBytes(1)->getInt();
            }),
            $this->uint32le,
            $this->uint32le,
            $this->uint8le
        ]);
    }

    /**
     * @param BloomFilter $filter
     * @return BufferInterface
     */
    public function serialize(BloomFilter $filter)
    {
        $parser = new Parser();
        $parser->appendBuffer(Buffertools::numToVarInt(count($filter->getData())));
        foreach ($filter->getData() as $i) {
            $parser->writeRawBinary(1, pack('c', $i));
        }

        $parser->writeRawBinary(4, $this->uint32le->write($filter->getNumHashFuncs()));
        $parser->writeRawBinary(4, $this->uint32le->write($filter->getTweak()));
        $parser->writeRawBinary(1, $this->uint8le->write($filter->getFlags()));

        return $parser->getBuffer();
    }

    /**
     * @param Parser $parser
     * @return BloomFilter
     */
    public function fromParser(Parser $parser)
    {
        list ($vData, $numHashFuncs, $nTweak, $flags) = $this->template->parse($parser);

        return new BloomFilter(
            Bitcoin::getMath(),
            $vData,
            $numHashFuncs,
            $nTweak,
            $flags
        );
    }

    /**
     * @param string|BufferInterface $data
     * @return BloomFilter
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }
}
