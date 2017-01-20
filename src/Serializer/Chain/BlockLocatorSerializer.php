<?php

namespace BitWasp\Bitcoin\Serializer\Chain;

use BitWasp\Bitcoin\Chain\BlockLocator;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Template;

class BlockLocatorSerializer
{

    /**
     * @var Template
     */
    private $template;

    public function __construct()
    {
        $this->varint = Types::varint();
        $this->bytestring32 = $bytestring32 = Types::bytestringle(32);
        $this->template = new Template([
            Types::vector(function (Parser $parser) use ($bytestring32) {
                return $bytestring32->read($parser);
            }),
            $bytestring32
        ]);
    }

    /**
     * @param Parser $parser
     * @return BlockLocator
     */
    public function fromParser(Parser $parser)
    {
        $numHashes = $this->varint->read($parser);
        $hashes = [];
        for ($i = 0; $i < $numHashes; $i++) {
            $hashes[] = $this->bytestring32->read($parser);
        }

        $hashStop = $this->bytestring32->read($parser);

        return new BlockLocator($hashes, $hashStop);
    }

    /**
     * @param BufferInterface|string $data
     * @return BlockLocator
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param BlockLocator $blockLocator
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function serialize(BlockLocator $blockLocator)
    {
        $binary = Buffertools::numToVarInt(count($blockLocator->getHashes()))->getBinary();
        foreach ($blockLocator->getHashes() as $hash) {
            $binary .= Buffertools::flipBytes($hash->getBinary());
        }

        $binary .= $blockLocator->getHashStop()->getBinary();
        return new Buffer($binary);
    }
}
