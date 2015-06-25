<?php

namespace BitWasp\Bitcoin\Network;

use BitWasp\Bitcoin\Chain\BlockIndex;
use BitWasp\Buffertools\Buffer;

class BlockLocator
{
    /**
     * @param int $height
     * @param BlockIndex $index
     * @param bool $all
     * @return array
     */
    public function hashes($height, BlockIndex $index, $all = false)
    {
        $step = 1;
        $vHash = [];
        $pIndex = $index->hash()->fetch($height);

        while (true) {
            array_push($vHash, Buffer::hex($pIndex, 32));
            if ($height == 0) {
                break;
            }

            $height = max($height - $step, 0);
            $pIndex = $index->hash()->fetch($height);
            if (count($vHash) >= 10) {
                $step *= 2;
            }
        }

        if ($all) {
            array_push($vHash, Buffer::hex('00', 32));
        }

        return $vHash;
    }
}
