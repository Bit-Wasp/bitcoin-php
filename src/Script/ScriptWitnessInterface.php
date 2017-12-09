<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Collection\CollectionInterface;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\SerializableInterface;

interface ScriptWitnessInterface extends CollectionInterface, SerializableInterface
{
    /**
     * @return BufferInterface[]
     */
    public function all();

    /**
     * @param ScriptWitnessInterface $witness
     * @return bool
     */
    public function equals(ScriptWitnessInterface $witness);
}
