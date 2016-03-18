<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\CommonTrait\FunctionAliasArrayAccess;

class OutPoint extends Serializable implements OutPointInterface
{
    use FunctionAliasArrayAccess;

    /**
     * @var BufferInterface
     */
    private $hashPrevOutput;

    /**
     * @var int
     */
    private $nPrevOutput;

    /**
     * OutPoint constructor.
     * @param BufferInterface $hashPrevOutput
     * @param int $nPrevOutput
     */
    public function __construct(BufferInterface $hashPrevOutput, $nPrevOutput)
    {
        if ($hashPrevOutput->getSize() !== 32) {
            throw new \InvalidArgumentException('OutPoint: hashPrevOut must be a 32-byte Buffer');
        }
        
        $this->hashPrevOutput = $hashPrevOutput;
        $this->nPrevOutput = $nPrevOutput;

        $this
            ->initFunctionAlias('txid', 'getTxId')
            ->initFunctionAlias('vout', 'getVout');
    }

    /**
     * @return BufferInterface
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
     * @param OutPointInterface $outPoint
     * @return int
     */
    public function equals(OutPointInterface $outPoint)
    {
        $txid = strcmp($this->getTxId()->getBinary(), $outPoint->getTxId()->getBinary());
        if ($txid !== 0) {
            return false;
        }

        return gmp_cmp($this->getVout(), $outPoint->getVout()) === 0;
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer()
    {
        return (new OutPointSerializer())->serialize($this);
    }
}
