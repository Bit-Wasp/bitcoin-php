<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Block\BlockHeader;
use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Buffertools\TemplateFactory;

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
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->uint32le()
            ->bytestringle(32)
            ->bytestringle(32)
            ->uint32le()
            ->bytestringle(4)
            ->uint32le()
            ->getTemplate();
    }

    /**
     * @param $parser
     * @return BlockHeader
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser & $parser)
    {

        try {
            list ($version, $prevHash, $merkleHash, $time, $nBits, $nonce) = $this->getTemplate()->parse($parser);

            return new BlockHeader(
                $version,
                $prevHash->getHex(),
                $merkleHash->getHex(),
                $time,
                $nBits,
                $nonce
            );
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full block header from parser');
        }
    }

    /**
     * @param BlockHeaderInterface $header
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(BlockHeaderInterface $header)
    {
        return $this->getTemplate()->write([
            $header->getVersion(),
            Buffer::hex($header->getPrevBlock()),
            Buffer::hex($header->getMerkleRoot()),
            $header->getTimestamp(),
            $header->getBits(),
            $header->getNonce()
        ]);
    }
}
