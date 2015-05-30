<?php

namespace BitWasp\Bitcoin\Key\Deterministic;

use BitWasp\Bitcoin\Math\Math;

class HierarchicalKeySequence
{
    /**
     * @var Math
     */
    private $math;

    const START_HARDENED = 2147483648; // 2^31

    /**
     * @param Math $math
     */
    public function __construct(Math $math)
    {
        $this->math = $math;
    }

    /**
     * @return \BitWasp\Bitcoin\Math\BinaryMath
     */
    private function binaryMath()
    {
        return $this->math->getBinaryMath();
    }

    /**
     * @param $sequence
     * @return bool
     */
    public function isHardened($sequence)
    {
        return $this->binaryMath()->isNegative($sequence, 32);
    }

    /**
     * @param $sequence
     * @return int|string
     */
    public function getHardened($sequence)
    {
        if ($this->isHardened($sequence)) {
            throw new \LogicException('Sequence is already for a hardened key');
        }

        $prime = $this->binaryMath()->makeNegative($sequence, 32);
        return $prime;
    }

    /**
     * Convert a human readable path node (eg, "0", "0'", or "0h") into the correct sequence (0, 0x80000000, 0x80000000)
     *
     * @param $node
     * @return int|string
     */
    public function fromNode($node)
    {
        $hardened = false;
        if (in_array(substr(strtolower($node), -1), array("h", "'")) === true) {
            $intEnd = strlen($node) - 1;
            $node = substr($node, 0, $intEnd);
            $hardened = true;
        }

        if ($hardened) {
            $node = $this->getHardened($node);
        }

        return $node;
    }

    /**
     * Given a sequence, get the human readable node. Ie, 0 -> 0, 0x80000000 -> 0h
     * @param $sequence
     * @return string
     */
    public function getNode($sequence)
    {
        if ($this->isHardened($sequence)) {
            $sequence = $this->math->sub($sequence, self::START_HARDENED) . 'h';
        }

        return $sequence;
    }
}
