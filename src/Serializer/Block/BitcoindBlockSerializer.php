<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Template;

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
        $this->template = new Template([
            Types::bytestringle(4),
            Types::uint32le()
        ]);
    }

    /**
     * @param BlockInterface $block
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function serialize(BlockInterface $block)
    {
        $buffer = $this->blockSerializer->serialize($block);
        $data = new Parser($this->template->write([
            Buffer::hex($this->network->getNetMagicBytes()),
            $buffer->getSize()
        ]));

        $data->appendBuffer($buffer);

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
        list ($bytes, $blockSize) = $this->template->parse($parser);
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
