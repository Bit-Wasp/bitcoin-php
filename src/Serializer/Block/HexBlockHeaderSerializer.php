<?php

namespace Afk11\Bitcoin\Serializer\Block;

use Afk11\Bitcoin\Exceptions\ParserOutOfRange;
use Afk11\Bitcoin\Parser;

use Afk11\Bitcoin\Block\BlockHeader;
use Afk11\Bitcoin\Block\BlockHeaderInterface;

class HexBlockHeaderSerializer
{

    /**
     * @param $string
     * @return BlockHeader
     * @throws ParserOutOfRange
     */
    public function parse($string)
    {
        $parser = new Parser($string);
        return $this->fromParser($parser);
    }

    /**
     * @param $parser
     * @return BlockHeader
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser &$parser)
    {
        $header = new BlockHeader();
        try {
            $header->setVersion($parser->readBytes(4, true)->serialize('int'));
            $header->setPrevBlock($parser->readBytes(32, true));
            $header->setMerkleRoot($parser->readBytes(32, true));
            $header->setTimestamp($parser->readBytes(4, true)->serialize('int'));
            $header->setBits($parser->readBytes(4, true));
            $header->setNonce($parser->readBytes(4, true)->serialize('int'));
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full block header from parser');
        }

        return $header;
    }

    /**
     * @param BlockHeaderInterface $header
     * @return string
     */
    public function serialize(BlockHeaderInterface $header)
    {
        $data = new Parser;
        $data->writeInt(4, $header->getVersion(), true);
        $data->writeBytes(32, $header->getPrevBlock(), true);
        $data->writeBytes(32, $header->getMerkleRoot(), true);
        $data->writeInt(4, $header->getTimestamp(), true);
        $data->writeBytes(4, $header->getBits(), true);
        $data->writeInt(4, $header->getNonce(), true);

        return $data->getBuffer();
    }
}
