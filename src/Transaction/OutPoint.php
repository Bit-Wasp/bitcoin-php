<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializer;
use BitWasp\Buffertools\Buffer;

class OutPoint extends Serializable implements OutPointInterface
{
    /**
     * @var Buffer
     */
    private $hashPrevOutput;

    /**
     * @var int
     */
    private $nPrevOutput;

    /**
     * OutPoint constructor.
     * @param Buffer $hashPrevOutput
     * @param int $nPrevOutput
     */
    public function __construct(Buffer $hashPrevOutput, $nPrevOutput)
    {
        if ($hashPrevOutput->getSize() !== 32) {
            throw new \InvalidArgumentException('OutPoint: hashPrevOut must be a 32-byte Buffer');
        }

        if (!is_numeric($nPrevOutput)) {
            throw new \InvalidArgumentException('OutPoint: nPrevOut must be numeric');
        }

        $this->hashPrevOutput = $hashPrevOutput;
        $this->nPrevOutput = $nPrevOutput;
    }

    /**
     * @return Buffer
     */
    public function getTxId()
    {
        return $this->hashPrevOutput;
    }

    /**
     * @return int
     */
    public function getVout()
    {
        return $this->nPrevOutput;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new OutPointSerializer())->serialize($this);
    }
}
