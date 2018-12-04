<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class Number extends Serializable
{
    const MAX_NUM_SIZE = 4;
    const MAX = 2**31-1;
    const MIN = -2**31+1;

    /**
     * @var Math
     */
    private $math;

    /**
     * @var int|string
     */
    private $number;

    /**
     * Number constructor.
     * @param int|string $number
     * @param Math $math
     */
    public function __construct($number, Math $math)
    {
        $this->number = $number;
        $this->math = $math;
    }

    /**
     * @param int|string $number
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
     * @param \GMP $number
     * @param Math|null $math
     * @return self
     */
    public static function gmp(\GMP $number, Math $math = null)
    {
        return new self(
            gmp_strval($number, 10),
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
            if ((ord($binary[$size - 1]) & 0x7f) === 0) {
                if ($size <= 1 || (ord($binary[$size - 2]) & 0x80) === 0) {
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
     * @return string
     */
    private function parseBuffer(BufferInterface $buffer): string
    {
        $size = $buffer->getSize();
        if ($size === 0) {
            return '0';
        }

        $chars = array_values(unpack("C*", $buffer->getBinary()));

        $result = gmp_init(0);
        for ($i = 0; $i < $size; $i++) {
            $mul = $i * 8;
            $byte = $this->math->leftShift(gmp_init($chars[$i], 10), $mul);
            $result = $this->math->bitwiseOr($result, $byte);
        }

        if ($chars[count($chars)-1] & 0x80) {
            $mask = gmp_com($this->math->leftShift(gmp_init(0x80), (8 * ($size - 1))));
            $result = $this->math->sub(gmp_init(0), $this->math->bitwiseAnd($result, $mask));
        }

        return gmp_strval($result, 10);
    }

    /**
     * @return BufferInterface
     */
    private function serialize(): BufferInterface
    {
        if ((int) $this->number === 0) {
            return new Buffer('', 0);
        }

        $zero = gmp_init(0);
        // Using array of integers instead of bytes
        $result = [];
        $negative = $this->math->cmp(gmp_init($this->number), $zero) < 0;
        $abs = $negative ? $this->math->sub($zero, gmp_init($this->number, 10)) : gmp_init($this->number, 10);
        $mask = gmp_init(0xff);
        while ($this->math->cmp($abs, $zero) > 0) {
            $result[] = (int) gmp_strval($this->math->bitwiseAnd($abs, $mask), 10);
            $abs = $this->math->rightShift($abs, 8);
        }

        if ($result[count($result) - 1] & 0x80) {
            $result[] = $negative ? 0x80 : 0;
        } else if ($negative) {
            $result[count($result) - 1] |= 0x80;
        }

        return new Buffer(pack("C*", ...$result));
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer(): BufferInterface
    {
        return $this->serialize();
    }

    /**
     * @return int
     */
    public function getInt(): int
    {
        if ($this->math->cmp(gmp_init($this->number, 10), gmp_init(self::MAX)) > 0) {
            return self::MAX;
        } else if ($this->math->cmp(gmp_init($this->number, 10), gmp_init(self::MIN)) < 0) {
            return self::MIN;
        }

        return (int) $this->number;
    }

    /**
     * @return \GMP
     */
    public function getGmp()
    {
        return gmp_init($this->number, 10);
    }
}
