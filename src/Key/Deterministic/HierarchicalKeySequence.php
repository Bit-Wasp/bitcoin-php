<?php

namespace BitWasp\Bitcoin\Key\Deterministic;

/**
 * NB: Paths returned by this library omit m/M. This is because
 * some knowledge is lost during derivations, so the full path
 * is already considered 'meta-data'. It also allows the library
 * to assume derivations are relative to the current instance.
 */
class HierarchicalKeySequence
{

    const START_HARDENED = 2147483648;

    /**
     * @param int $sequence
     * @return bool
     */
    public function isHardened($sequence)
    {
        return ($sequence >> 31) === 1;
    }

    /**
     * @param int $sequence
     * @return int
     */
    public function getHardened($sequence)
    {
        if ($this->isHardened($sequence)) {
            throw new \LogicException('Sequence is already for a hardened key');
        }

        $flag = 1 << 31;
        $hardened = $sequence | $flag;

        return $hardened;
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
        if (in_array(substr(strtolower($node), -1), array('h', "'"), true) === true) {
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
     *
     * @param int $sequence
     * @return string
     */
    public function getNode($sequence)
    {
        if ($this->isHardened($sequence)) {
            $sequence = $sequence - self::START_HARDENED;
            $sequence = (string) $sequence . 'h';
        }

        return $sequence;
    }

    /**
     * Decodes a human-readable path, into an array of integers (sequences)
     *
     * @param string $path
     * @return array
     */
    public function decodePath($path)
    {
        if ($path === '') {
            throw new \InvalidArgumentException('Invalid path passed to decodePath()');
        }

        $list = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment !== 'm' && $segment !== 'M') {
                $list[] = $this->fromNode($segment);
            }
        }

        return $list;
    }

    /**
     * Encodes a list of sequences to the human-readable path.
     *
     * @param array|\stdClass|\Traversable $list
     * @return string
     */
    public function encodePath($list)
    {
        self::validateListType($list);

        $path = [];
        foreach ($list as $sequence) {
            $path[] = $this->getNode($sequence);
        }

        return implode('/', $path);
    }

    /**
     * Check the list, mainly that it works for foreach()
     *
     * @param \stdClass|array|\Traversable $list
     */
    public static function validateListType($list)
    {
        if (!is_array($list) && !$list instanceof \Traversable && !$list instanceof \stdClass) {
            throw new \InvalidArgumentException('Sequence list must be an array or \Traversable');
        }
    }
}
