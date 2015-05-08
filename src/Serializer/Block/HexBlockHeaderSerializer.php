<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Block\BlockHeader;
use BitWasp\Bitcoin\Block\BlockHeaderInterface;

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
    public function fromParser(Parser & $parser)
    {

        try {
            $header = new BlockHeader(
                $parser->readBytes(4, true)->getInt(),
                $parser->readBytes(32, true)->getHex(),
                $parser->readBytes(32, true)->getHex(),
                $parser->readBytes(4, true)->getInt(),
                $parser->readBytes(4, true),
                $parser->readBytes(4, true)->getInt()
            );

        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full block header from parser');
        }

        return $header;
    }

    /**
     * @param BlockHeaderInterface $header
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(BlockHeaderInterface $header)
    {
        $data = new Parser;
        $data
            ->writeInt(4, $header->getVersion(), true)
            ->writeBytes(32, $header->getPrevBlock(), true)
            ->writeBytes(32, $header->getMerkleRoot(), true)
            ->writeInt(4, $header->getTimestamp(), true)
            ->writeBytes(4, $header->getBits(), true)
            ->writeInt(4, $header->getNonce(), true);

        return $data->getBuffer();
    }
}
