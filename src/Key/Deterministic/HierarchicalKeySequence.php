<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\Deterministic;

class HierarchicalKeySequence
{
    private static $filterBip32Index = [
        'min_range' => 0,
        'max_range' => (1 << 31) - 1,
    ];

    /**
     * decodeAbsolute accepts an absolute BIP32 path, one beginning
     * with m or M. It returns an array, containing two elements.
     * The first is whether the prefix was public or private.
     * The second is the array of indices contained in the path.
     *
     * @param string $path
     * @return array - <isPrivate bool, path <int[]>>
     */
    public function decodeAbsolute(string $path)
    {
        $parts = explode("/", $path);
        if (count($parts) < 1) {
            throw new \InvalidArgumentException("Invalid BIP32 path - must have at least one component");
        }

        if (!($parts[0] === "m" || $parts[0] === "M")) {
            throw new \InvalidArgumentException("Invalid start of absolute BIP32 path - should be m or M");
        }

        return [
            $parts[0] === "m",
            $this->decodeDerivation(...array_slice($parts, 1)),
        ];
    }

    /**
     * decodeRelative accepts a relative BIP32 path, that is,
     * one without the prefix of m or M. These are usually provided
     * when requesting some child derivation of a key.
     *
     * @param string $path
     * @return int[]
     */
    public function decodeRelative(string $path): array
    {
        $parts = explode("/", $path);
        if (count($parts) < 1) {
            throw new \InvalidArgumentException("Invalid BIP32 path - must have at least one component");
        }

        if ($parts[0] === "m" || $parts[0] === "M") {
            throw new \InvalidArgumentException("Only relative paths accepted");
        }

        return $this->decodeDerivation(...$parts);
    }

    /**
     * Inner routine for decoding the numeric section of a path
     * @param string ...$parts
     * @return int[]
     */
    private function decodeDerivation(string... $parts): array
    {
        $indices = [];
        foreach ($parts as $i => $part) {
            if ($part === "") {
                throw new \InvalidArgumentException("Invalid BIP32 path - Empty path section");
            }

            // test the last character for hardened
            $last = substr($part, -1);
            $hardened = $last == "h" || $last == "'";
            if ($hardened) {
                if (strlen($part) === 1) {
                    throw new \InvalidArgumentException("Invalid BIP32 path - section contains only hardened flag");
                }
                $part = substr($part, 0, -1);
            }

            // any other hardened chars are invalid
            if (false !== strpos($part, "h") || false !== strpos($part, "'")) {
                throw new \InvalidArgumentException("Invalid BIP32 path - section contains extra hardened characters");
            }

            // validate part is a valid integer
            if (false === filter_var($part, FILTER_VALIDATE_INT, self::$filterBip32Index)) {
                throw new \InvalidArgumentException("Index is invalid or outside valid range: $part");
            }

            // make index from int $part + $hardened
            $index = (int) $part;
            if ($hardened) {
                $index |= 1 << 31;
            }
            $indices[] = $index;
        }
        return $indices;
    }
}
