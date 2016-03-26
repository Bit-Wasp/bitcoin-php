<?php

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class Number extends Serializable
{
    const MAX_NUM_SIZE = 4;
    const MAX = 2147483647; // 2^31-1
    const MIN = -2147483647; // -2^31+1

    /**
     * @var Math
     */
    private $math;

    /**
     * @var int
     */
    private $number;

    /**
     * Number constructor.
     * @param int $number
     * @param Math $math
     */
    public function __construct($number, Math $math)
    {
        $this->number = $number;
        $this->math = $math;
    }

    /**
     * @param int $number
     * @param Math|null $math
     * @return self
     */
    public static function int($number, Math $math = null)
    {
        return new self(
            $number,
            $math ?: Bitcoin::getMath()
        );
    }

    /**
     * @param BufferInterface $vch
     * @param bool $fRequireMinimal
     * @param int $maxNumSize
     * @param Math|null $math
     * @return self
     */
    public static function buffer(BufferInterface $vch, $fRequireMinimal, $maxNumSize = self::MAX_NUM_SIZE, Math $math = null)
    {
        $size = $vch->getSize();
        if ($size > $maxNumSize) {
            throw new \RuntimeException('Script number overflow');
        }

        if ($fRequireMinimal && $size > 0) {
            $binary = $vch->getBinary();
            if (ord($binary[$size - 1]) & 0x7f === 0) {
                if ($size <= 1 || ord($binary[$size - 2]) & 0x80 === 0) {
                    throw new \RuntimeException('Non-minimally encoded script number');
                }
            }
        }

        $math = $math ?: Bitcoin::getMath();
        $number = new self(0, $math);
        $number->number = $number->parseBuffer($vch);
        return $number;
    }

    /**
     * @param BufferInterface $buffer
     * @return int
     */
    private function parseBuffer(BufferInterface $buffer)
    {
        $size = $buffer->getSize();
        if ($size === 0) {
            return '0';
        }

        $chars = array_map(function ($binary) {
            return ord($binary);

        }, str_split($buffer->getBinary(), 1));

        $result = 0;
        for ($i = 0; $i < $size; $i++) {
            $mul = $this->math->mul($i, 8);
            $byte = $this->math->leftShift($chars[$i], $mul);
            $result = $this->math->bitwiseOr($result, $byte);
        }

        if ($chars[count($chars)-1] & 0x80) {
            $mask = gmp_strval(gmp_com($this->math->leftShift(0x80, (8 * ($size - 1)))), 10);
            return $this->math->sub(0, $this->math->bitwiseAnd($result, $mask));
        }

        return $result;
    }

    /**
     * @return BufferInterface
     */
    private function serialize()
    {
        if ($this->math->cmp($this->number, '0') === 0) {
            return new Buffer('', 0);
        }

        // Using array of integers instead of bytes
        $result = [];
        $negative = $this->math->cmp($this->number, 0) < 0;
        $abs = $negative ? $this->math->sub(0, $this->number) : $this->number;

        while ($this->math->cmp($abs, 0) > 0) {
            $result[] = (int)$this->math->bitwiseAnd($abs, 0xff);
            $abs = $this->math->rightShift($abs, 8);
        }

        if ($result[count($result) - 1] & 0x80) {
            $result[] = $negative ? 0x80 : 0;
        } else if ($negative) {
            $result[count($result) - 1] |= 0x80;
        }

        $s = '';
        foreach ($result as $i) {
            $s .= chr($i);
        }

        return new Buffer($s, null, $this->math);

    }

    /**
     * @return BufferInterface
     */
    public function getBuffer()
    {
        return $this->serialize();
    }

    /**
     * @return int
     */
    public function getInt()
    {
        if ($this->math->cmp($this->number, self::MAX) > 0) {
            return self::MAX;
        } else if ($this->math->cmp($this->number, self::MIN) < 0) {
            return self::MIN;
        }

        return $this->number;
    }
}
