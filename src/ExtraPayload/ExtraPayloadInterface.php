<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\ExtraPayload;

use BitWasp\Bitcoin\Collection\CollectionInterface;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\SerializableInterface;

interface ExtraPayloadInterface extends CollectionInterface, SerializableInterface
{
    /**
     * @return BufferInterface
     */
    public function getBuffer(): BufferInterface;

    /**
     * @param ExtraPayloadInterface $extra_payload_size
     * @return int
     */
    public function getSize(): int;

    /**
     * @param ExtraPayloadInterface $extra_payload_hex
     * @return string
     */
    public function getHex(): string;
}
