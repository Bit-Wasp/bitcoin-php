<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\SerializableInterface;
use BitWasp\Buffertools\Buffer;

interface OutPointInterface extends SerializableInterface
{
    /**
     * @return Buffer
     */
    public function getTxId();

    /**
     * @return int
     */
    public function getVout();
}
