<?php

namespace BitWasp\Bitcoin\Network\Structure;

use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Serializable;

class InventoryVector extends Serializable
{
    const ERROR = 0;
    const MSG_TX = 1;
    const MSG_BLOCK = 2;
    const MSG_FILTERED_BLOCK = 3;

    /**
     * @var int
     */
    private $type;

    /**
     * @var Buffer
     */
    private $hash;

    /**
     * @param $type
     * @param Buffer $hash
     */
    public function __construct($type, Buffer $hash)
    {
        if (false === $this->checkType($type)) {
            throw new \InvalidArgumentException('Invalid type in InventoryVector');
        }

        if (false === (32 === $hash->getSize())) {
            throw new \InvalidArgumentException('Hash size must be 32 bytes');
        }

        $this->type = $type;
        $this->hash = $hash;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Buffer
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param $type
     * @return bool
     */
    private function checkType($type)
    {
        return (true === in_array($type, [self::ERROR, self::MSG_TX, self::MSG_BLOCK, self::MSG_FILTERED_BLOCK]));
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        $parser = new Parser();
        $parser
            ->writeInt(4, $this->type, true)
            ->writeBytes(32, $this->hash);

        return $parser->getBuffer();
    }
}
