<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Block\BlockHeader;
use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Buffertools\Template;
use BitWasp\Buffertools\TemplateFactory;

class BlockHeaderSerializer
{
    /**
     * @var \BitWasp\Buffertools\Template
     */
    private $template;

    public function __construct()
    {
        $this->template = $this->getTemplate();
    }

    /**
     * @param \BitWasp\Buffertools\BufferInterface|string $string
     * @return BlockHeader
     * @throws ParserOutOfRange
     */
    public function parse($string)
    {
        return $this->fromParser(new Parser($string));
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        $bsLE = Types::bytestringle(32);
        $uint32le = Types::uint32le();
        return new Template([
            Types::int32le(),
            $bsLE,
            $bsLE,
            $uint32le,
            $uint32le,
            $uint32le
        ]);
    }

    /**
     * @param Parser $parser
     * @return BlockHeader
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser $parser)
    {
        try {
            list ($version, $prevHash, $merkleHash, $time, $nBits, $nonce) = $this->template->parse($parser);

            return new BlockHeader(
                $version,
                $prevHash,
                $merkleHash,
                $time,
                (int) $nBits,
                $nonce
            );
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full block header from parser');
        }
    }

    /**
     * @param BlockHeaderInterface $header
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function serialize(BlockHeaderInterface $header)
    {
        return $this->template->write([
            $header->getVersion(),
            $header->getPrevBlock(),
            $header->getMerkleRoot(),
            $header->getTimestamp(),
            $header->getBits(),
            $header->getNonce()
        ]);
    }
}
