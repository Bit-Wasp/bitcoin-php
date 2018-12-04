<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionInputSerializer;
use BitWasp\Buffertools\BufferInterface;

class TransactionInput extends Serializable implements TransactionInputInterface
{
    /**
     * @var OutPointInterface
     */
    private $outPoint;

    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @var int
     */
    private $sequence;

    /**
     * @param OutPointInterface $outPoint
     * @param ScriptInterface $script
     * @param int $sequence
     */
    public function __construct(OutPointInterface $outPoint, ScriptInterface $script, int $sequence = self::SEQUENCE_FINAL)
    {
        $this->outPoint = $outPoint;
        $this->script = $script;
        $this->sequence = $sequence;
    }

    /**
     * @return OutPointInterface
     */
    public function getOutPoint(): OutPointInterface
    {
        return $this->outPoint;
    }

    /**
     * @return ScriptInterface
     */
    public function getScript(): ScriptInterface
    {
        return $this->script;
    }

    /**
     * @return int
     */
    public function getSequence(): int
    {
        return $this->sequence;
    }

    /**
     * @param TransactionInputInterface $other
     * @return bool
     */
    public function equals(TransactionInputInterface $other): bool
    {
        if (!$this->outPoint->equals($other->getOutPoint())) {
            return false;
        }

        if (!$this->script->equals($other->getScript())) {
            return false;
        }

        return gmp_cmp(gmp_init($this->sequence), gmp_init($other->getSequence())) === 0;
    }

    /**
     * Check whether this transaction is a Coinbase transaction
     *
     * @return bool
     */
    public function isCoinbase(): bool
    {
        $outpoint = $this->outPoint;
        return $outpoint->getTxId()->getBinary() === str_pad('', 32, "\x00")
            && $outpoint->getVout() == 0xffffffff;
    }

    /**
     * @return bool
     */
    public function isFinal(): bool
    {
        $math = Bitcoin::getMath();
        return $math->cmp(gmp_init($this->getSequence(), 10), gmp_init(self::SEQUENCE_FINAL, 10)) === 0;
    }

    /**
     * @return bool
     */
    public function isSequenceLockDisabled(): bool
    {
        if ($this->isCoinbase()) {
            return true;
        }

        return ($this->sequence & self::SEQUENCE_LOCKTIME_DISABLE_FLAG) !== 0;
    }

    /**
     * @return bool
     */
    public function isLockedToTime(): bool
    {
        return !$this->isSequenceLockDisabled() && (($this->sequence & self::SEQUENCE_LOCKTIME_TYPE_FLAG) === self::SEQUENCE_LOCKTIME_TYPE_FLAG);
    }

    /**
     * @return bool
     */
    public function isLockedToBlock(): bool
    {
        return !$this->isSequenceLockDisabled() && (($this->sequence & self::SEQUENCE_LOCKTIME_TYPE_FLAG) === 0);
    }

    /**
     * @return int
     */
    public function getRelativeTimeLock(): int
    {
        if (!$this->isLockedToTime()) {
            throw new \RuntimeException('Cannot decode time based locktime when disable flag set/timelock flag unset/tx is coinbase');
        }

        // Multiply by 512 to convert locktime to seconds
        return ($this->sequence & self::SEQUENCE_LOCKTIME_MASK) * 512;
    }

    /**
     * @return int
     */
    public function getRelativeBlockLock(): int
    {
        if (!$this->isLockedToBlock()) {
            throw new \RuntimeException('Cannot decode block locktime when disable flag set/timelock flag set/tx is coinbase');
        }

        return $this->sequence & self::SEQUENCE_LOCKTIME_MASK;
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer(): BufferInterface
    {
        return (new TransactionInputSerializer(new OutPointSerializer()))->serialize($this);
    }
}
