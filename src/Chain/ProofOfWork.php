<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class ProofOfWork
{
    const DIFF_PRECISION = 12;
    const POW_2_256 = '115792089237316195423570985008687907853269984665640564039457584007913129639936';

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
     * @param int $bits
     * @return \GMP
     */
    public function getTarget(int $bits): \GMP
    {
        $negative = false;
        $overflow = false;
        return $this->math->decodeCompact($bits, $negative, $overflow);
    }

    /**
     * @return \GMP
     */
    public function getMaxTarget(): \GMP
    {
        return $this->getTarget($this->params->powBitsLimit());
    }

    /**
     * @param int $bits
     * @return BufferInterface
     */
    public function getTargetHash(int $bits): BufferInterface
    {
        return Buffer::int(gmp_strval($this->getTarget($bits), 10), 32);
    }

    /**
     * @param int $bits
     * @return string
     */
    public function getDifficulty(int $bits): string
    {
        $target = $this->getTarget($bits);
        $lowest = $this->getMaxTarget();
        $lowest = $this->math->mul($lowest, $this->math->pow(gmp_init(10, 10), self::DIFF_PRECISION));
        
        $difficulty = str_pad($this->math->toString($this->math->div($lowest, $target)), self::DIFF_PRECISION + 1, '0', STR_PAD_LEFT);
        
        $intPart = substr($difficulty, 0, 0 - self::DIFF_PRECISION);
        $decPart = substr($difficulty, 0 - self::DIFF_PRECISION, self::DIFF_PRECISION);
        
        return $intPart . '.' . $decPart;
    }

    /**
     * @param BufferInterface $hash
     * @param int $nBits
     * @return bool
     */
    public function checkPow(BufferInterface $hash, int $nBits): bool
    {
        $negative = false;
        $overflow = false;
        
        $target = $this->math->decodeCompact($nBits, $negative, $overflow);
        if ($negative || $overflow || $this->math->cmp($target, gmp_init(0)) === 0 ||  $this->math->cmp($target, $this->getMaxTarget()) > 0) {
            throw new \RuntimeException('nBits below minimum work');
        }

        if ($this->math->cmp($hash->getGmp(), $target) > 0) {
            return false;
        }

        return true;
    }

    /**
     * @param BlockHeaderInterface $header
     * @return bool
     * @throws \Exception
     */
    public function checkHeader(BlockHeaderInterface $header): bool
    {
        return $this->checkPow($header->getHash(), $header->getBits());
    }

    /**
     * @param int $bits
     * @return \GMP
     */
    public function getWork(int $bits): \GMP
    {
        $target = gmp_strval($this->getTarget($bits), 10);
        return gmp_init(bcdiv(self::POW_2_256, $target), 10);
    }
}
