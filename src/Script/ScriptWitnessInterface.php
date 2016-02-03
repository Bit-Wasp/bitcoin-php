<?php
/**
 * Created by PhpStorm.
 * User: tk
 * Date: 14/01/16
 * Time: 02:36
 */
namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Collection\CollectionInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\SerializableInterface;

interface ScriptWitnessInterface extends CollectionInterface, SerializableInterface
{
    /**
     * @return BufferInterface
     */
    public function bottom();

    /**
     * @param int $start
     * @param int $length
     * @return ScriptWitness
     */
    public function slice($start, $length);
}
