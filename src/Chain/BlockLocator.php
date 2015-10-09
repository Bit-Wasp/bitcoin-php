<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Serializer\Chain\BlockLocatorSerializer;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Buffertools\Buffer;

class BlockLocator extends Serializable
{
    /**
     * @var Buffer[]
     */
    private $hashes;

    /**
     * @var Buffer
     */
    private $hashStop;

    /**
     * @param Buffer[] $hashes
     * @param Buffer $hashStop
     */
    public function __construct(array $hashes, Buffer $hashStop)
    {
        foreach ($hashes as $hash) {
            $this->addHash($hash);
        }

        $this->hashStop = $hashStop;
    }

    /**
     * @param Buffer $hash
     */
    private function addHash(Buffer $hash)
    {
        $this->hashes[] = $hash;
    }

    /**
     * @return \BitWasp\Buffertools\Buffer[]
     */
    public function getHashes()
    {
        return $this->hashes;
    }

    /**
     * @return Buffer
     */
    public function getHashStop()
    {
        return $this->hashStop;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new BlockLocatorSerializer())->serialize($this);
    }
}
