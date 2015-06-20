<?php

namespace BitWasp\Bitcoin\Network;

use BitWasp\Bitcoin\Chain\BlockIndex;
use BitWasp\Buffertools\Buffer;

class BlockLocator
{
    /**
     * @param $height
     * @param BlockIndex $index
     * @param bool $all
     * @return array
     */
    public function hashes($height, BlockIndex $index, $all = true)
    {
        $hash = [];
        for ($step = 1, $start = 0, $i = $height; $i > 0; $i -= $step, ++$start) {
            if ($start >= 0) {
                $step *= 2;
            }

            $hash[] = Buffer::hex($index->hash()->fetch($i));
        }

        $hash[] = Buffer::hex('', 32);

        return $hash;
    }
}
