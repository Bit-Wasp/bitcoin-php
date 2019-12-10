<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script;

use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\SerializableInterface;

interface ScriptWitnessInterface extends SerializableInterface, \Iterator, \ArrayAccess, \Countable
{
    /**
     * @return BufferInterface
     */
    public function bottom(): BufferInterface;

    /**
     * @return mixed
     */
    public function top(): BufferInterface;

    /**
     * @param int $start
     * @param int $length
     * @return ScriptWitnessInterface
     */
    public function slice(int $start, int $length): ScriptWitnessInterface;

    /**
     * @return bool
     */
    public function isNull(): bool;

    /**
     * @return BufferInterface[]
     */
    public function all(): array;

    /**
     * @param ScriptWitnessInterface $witness
     * @return bool
     */
    public function equals(ScriptWitnessInterface $witness): bool;
}
