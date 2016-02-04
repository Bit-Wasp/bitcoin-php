<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class BitcoindBlockSerializer
{
    /**
     * @var NetworkInterface
     */
    private $network;

    /**
     * @var BlockSerializer
     */
    private $blockSerializer;

    /**
     * @param NetworkInterface $network
     * @param BlockSerializer $blockSerializer
     */
    public function __construct(NetworkInterface $network, BlockSerializer $blockSerializer)
    {
        $this->network = $network;
        $this->blockSerializer = $blockSerializer;
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    private function getHeaderTemplate()
    {
        return (new TemplateFactory())
            ->bytestringle(4)
            ->uint32le()
            ->getTemplate();
    }

    /**
     * @param BlockInterface $block
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function serialize(BlockInterface $block)
    {
        $buffer = $block->getBuffer();
        $size = $buffer->getSize();
        $data = new Parser($this->getHeaderTemplate()->write([
            Buffer::hex($this->network->getNetMagicBytes()),
            $size
        ]));

        $data->writeBytes($size, $buffer);

        return $data->getBuffer();
    }

    /**
     * @param Parser $parser
     * @return \BitWasp\Bitcoin\Block\Block
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function fromParser(Parser $parser)
    {
        /** @var Buffer $bytes */
        /** @var int|string $blockSize */
        list ($bytes, $blockSize) = $this->getHeaderTemplate()->parse($parser);
        if ($bytes->getHex() !== $this->network->getNetMagicBytes()) {
            throw new \RuntimeException('Block version bytes did not match network');
        }

        return $this->blockSerializer->fromParser(new Parser($parser->readBytes($blockSize)));
    }

    /**
     * @param \BitWasp\Buffertools\BufferInterface|string $data
     * @return \BitWasp\Bitcoin\Block\Block
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }
}
