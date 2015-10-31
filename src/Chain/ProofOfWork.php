<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Buffertools\Buffer;

class ProofOfWork
{
    const DIFF_PRECISION = 12;

    /**
     * @var Math
     */
    private $math;

    /**
     * @var ParamsInterface
     */
    private $params;

    /**
     * @param Math $math
     * @param ParamsInterface $params
     */
    public function __construct(Math $math, ParamsInterface $params)
    {
        $this->math = $math;
        $this->params = $params;
    }

    /**
     * @param Buffer $bits
     * @return int|string
     */
    public function getTarget(Buffer $bits)
    {
        $negative = false;
        $overflow = false;
        return $this->math->compact()->set($bits->getInt(), $negative, $overflow);
    }

    /**
     * @return int|string
     */
    public function getMaxTarget()
    {
        return $this->getTarget(Buffer::int($this->params->powBitsLimit(), 4, $this->math));
    }

    /**
     * @param Buffer $bits
     * @return Buffer
     */
    public function getTargetHash(Buffer $bits)
    {
        return Buffer::int(
            $this->getTarget($bits),
            32,
            $this->math
        );
    }

    /**
     * @param Buffer $bits
     * @return string
     */
    public function getDifficulty(Buffer $bits)
    {
        $target = $this->getTarget($bits);
        $lowest = $this->getMaxTarget();
        $lowest = $this->math->mul($lowest, $this->math->pow(10, self::DIFF_PRECISION));
        
        $difficulty = str_pad($this->math->div($lowest, $target), self::DIFF_PRECISION + 1, '0', STR_PAD_LEFT);
        
        $intPart = substr($difficulty, 0, 0 - self::DIFF_PRECISION);
        $decPart = substr($difficulty, 0 - self::DIFF_PRECISION, self::DIFF_PRECISION);
        
        return $intPart . '.' . $decPart;
    }

    /**
     * @param Buffer $hash
     * @param int|string $nBits
     * @return bool
     */
    public function check(Buffer $hash, $nBits)
    {
        $negative = false;
        $overflow = false;
        $target = $this->math->compact()->set($nBits, $negative, $overflow);
        if ($negative || $overflow || $this->math->cmp($target, 0) === 0 ||  $this->math->cmp($target, $this->getMaxTarget()) > 0) {
            throw new \RuntimeException('nBits below minimum work');
        }

        if ($this->math->cmp($hash->getInt(), $target) > 0) {
            throw new \RuntimeException("Hash doesn't match nBits");
        }

        return true;
    }

    /**
     * @param BlockHeaderInterface $header
     * @return bool
     * @throws \Exception
     */
    public function checkHeader(BlockHeaderInterface $header)
    {
        return $this->check($header->getHash(), $header->getBits()->getInt());
    }

    /**
     * @param Buffer $bits
     * @return int|string
     */
    public function getWork(Buffer $bits)
    {
        return bcdiv($this->math->pow(2, 256), $this->getTarget($bits));
    }

    /**
     * @param BlockHeaderInterface[] $blocks
     * @return int|string
     */
    public function sumWork(array $blocks)
    {
        $work = 0;
        foreach ($blocks as $header) {
            $work = $this->math->add($this->getWork($header->getBits()), $work);
        }

        return $work;
    }

    /**
     * @param BlockHeaderInterface[] $blockSet1
     * @param BlockHeaderInterface[] $blockSet2
     * @return int
     */
    public function compareWork($blockSet1, $blockSet2)
    {
        return $this->math->cmp(
            $this->sumWork($blockSet1),
            $this->sumWork($blockSet2)
        );
    }
}
