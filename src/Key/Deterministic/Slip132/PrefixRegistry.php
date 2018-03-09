<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\Deterministic\Slip132;

class PrefixRegistry
{
    /**
     * @var array
     */
    private $registry = [];

    /**
     * PrefixRegistry constructor.
     * @param array $registry
     */
    public function __construct(array $registry)
    {
        foreach ($registry as $scriptType => $prefixes) {
            if (!is_string($scriptType)) {
                throw new \InvalidArgumentException("Expecting script type as key");
            }
            if (count($prefixes) !== 2) {
                throw new \InvalidArgumentException("Expecting two BIP32 prefixes");
            }
            // private, public
            if (strlen($prefixes[0]) !== 8 || !ctype_xdigit($prefixes[0])) {
                throw new \InvalidArgumentException("Invalid private prefix");
            }
            if (strlen($prefixes[1]) !== 8 || !ctype_xdigit($prefixes[1])) {
                throw new \InvalidArgumentException("Invalid public prefix");
            }
        }
        $this->registry = $registry;
    }

    /**
     * @param string $scriptType
     * @return array
     */
    public function getPrefixes($scriptType): array
    {
        if (!array_key_exists($scriptType, $this->registry)) {
            throw new \InvalidArgumentException("Unknown script type");
        }
        return $this->registry[$scriptType];
    }
}
