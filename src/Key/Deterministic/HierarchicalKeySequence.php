<?php

declare(strict_types=1);

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
    public function isHardened(int $sequence): bool
    {
        return ($sequence >> 31) === 1;
    }

    /**
     * @param int $sequence
     * @return int
     */
    public function getHardened(int $sequence): int
    {
        if ($this->isHardened($sequence)) {
            throw new \LogicException('Sequence is already for a hardened key');
        }

        $flag = 1 << 31;
        $hardened = $sequence | $flag;

        return (int) $hardened;
    }

    /**
     * Convert a human readable path node (eg, "0", "0'", or "0h") into the correct sequence (0, 0x80000000, 0x80000000)
     *
     * @param string $node
     * @return int
     */
    public function fromNode(string $node): int
    {
        if (strlen($node) < 1) {
            throw new \RuntimeException("Invalid node in sequence - empty value");
        }

        $last = substr(strtolower($node), -1);
        $hardened = false;
        if ($last === "h" || $last === "'") {
            $node = substr($node, 0, -1);
            $hardened = true;
        }

        $node = (int) $node;
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
    public function getNode(int $sequence): string
    {
        if ($this->isHardened($sequence)) {
            $sequence = $sequence - self::START_HARDENED;
            $sequence = (string) $sequence . 'h';
        }

        return (string) $sequence;
    }

    /**
     * Decodes a human-readable path, into an array of integers (sequences)
     *
     * @param string $path
     * @return int[]
     */
    public function decodePath(string $path): array
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
            $path[] = $this->getNode((int) $sequence);
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
