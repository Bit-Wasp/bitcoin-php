<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\SerializableInterface;
use BitWasp\Buffertools\BufferInterface;

interface OutPointInterface extends SerializableInterface
{
    /**
     * @return BufferInterface
     */
    public function getTxId(): BufferInterface;

    /**
     * @return int
     */
    public function getVout(): int;

    /**
     * @param OutPointInterface $outPoint
     * @return bool
     */
    public function equals(OutPointInterface $outPoint): bool;
}
