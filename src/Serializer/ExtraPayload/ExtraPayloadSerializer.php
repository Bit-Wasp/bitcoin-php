<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Serializer\ExtraPayload;

use BitWasp\Bitcoin\ExtraPayload\ExtraPayload;
use BitWasp\Bitcoin\ExtraPayload\ExtraPayloadInterface;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;

class ExtraPayloadSerializer
{
    /**
     * @var \BitWasp\Buffertools\Types\VarInt
     */
    private $varint;

    public function __construct()
    {
        $this->varint = Types::varint();
    }

    /**
     * @param Parser $parser
     * @return ExtraPayloadInterface
     */
    public function fromParser(Parser $parser): ExtraPayloadInterface
    {
        $extra_payload_size = $this->varint->read($parser);

        if ($extra_payload_size > $parser->getSize() - $parser->getPosition()) {
            throw new ParserOutOfRange("Invalid Extra Payload Size");
        }

        $extraPayloadBuffer = $parser->readBytes((int) $extra_payload_size);

        return new ExtraPayload($extraPayloadBuffer);
    }

    /**
     * @param ExtraPayloadInterface $extra_payload
     * @return BufferInterface
     */
    public function getBuffer(ExtraPayloadInterface $extra_payload): BufferInterface
    {
        return $extra_payload[0];
    }
}
