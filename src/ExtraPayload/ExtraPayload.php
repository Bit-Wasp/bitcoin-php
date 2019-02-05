<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\ExtraPayload;

use BitWasp\Bitcoin\Collection\StaticBufferCollection;
use BitWasp\Bitcoin\Serializer\ExtraPayload\ExtraPayloadSerializer;
use BitWasp\Buffertools\BufferInterface;

class ExtraPayload extends StaticBufferCollection implements ExtraPayloadInterface
{
    /**
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function getBuffer(): BufferInterface
    {
        return (new ExtraPayloadSerializer())->getBuffer($this);
    }

    public function getSize(): int
    {
        return (new ExtraPayloadSerializer())->getBuffer($this)->getSize();
    }

    public function getHex(): string
    {
        return (new ExtraPayloadSerializer())->getBuffer($this)->getHex();
    }
}
