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

    /**
     * @param int $height
     * @param BlockIndex $index
     * @param bool $all
     * @return BlockLocator
     */
    public static function create($height, BlockIndex $index, $all = false)
    {
        $step = 1;
        $hashes = [];
        $pIndex = $index->hash()->fetch($height);

        while (true) {
            array_push($hashes, Buffer::hex($pIndex, 32));
            if ($height == 0) {
                break;
            }

            $height = max($height - $step, 0);
            $pIndex = $index->hash()->fetch($height);
            if (count($hashes) >= 10) {
                $step *= 2;
            }
        }

        if ($all || count($hashes) == 1) {
            $hashStop = Buffer::hex('00', 32);
        } else {
            $hashStop = array_pop($hashes);
        }

        return new self(
            $hashes,
            $hashStop
        );
    }
}
