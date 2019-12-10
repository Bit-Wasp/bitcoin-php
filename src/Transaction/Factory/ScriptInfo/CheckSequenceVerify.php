<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Factory\ScriptInfo;

use BitWasp\Bitcoin\Locktime;
use BitWasp\Bitcoin\Script\Interpreter\Number;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\TransactionInput;

class CheckSequenceVerify
{
    /**
     * @var int
     */
    private $relativeTimeLock;

    /**
     * CheckLocktimeVerify constructor.
     * @param int $relativeTimeLock
     */
    public function __construct(int $relativeTimeLock)
    {
        if ($relativeTimeLock < 0) {
            throw new \RuntimeException("relative locktime cannot be negative");
        }

        if ($relativeTimeLock > Locktime::INT_MAX) {
            throw new \RuntimeException("nLockTime exceeds maximum value");
        }

        $this->relativeTimeLock = $relativeTimeLock;
    }

    /**
     * @param Operation[] $chunks
     * @param bool $fMinimal
     * @return static
     */
    public static function fromDecodedScript(array $chunks, $fMinimal = false): CheckSequenceVerify
    {
        if (count($chunks) !== 3) {
            throw new \RuntimeException("Invalid number of items for CSV");
        }

        if (!$chunks[0]->isPush()) {
            throw new \InvalidArgumentException('CSV script had invalid value for time');
        }

        if ($chunks[1]->getOp() !== Opcodes::OP_CHECKSEQUENCEVERIFY) {
            throw new \InvalidArgumentException('CSV script invalid opcode');
        }

        if ($chunks[2]->getOp() !== Opcodes::OP_DROP) {
            throw new \InvalidArgumentException('CSV script invalid opcode');
        }

        $numLockTime = Number::buffer($chunks[0]->getData(), $fMinimal, 5);

        return new CheckSequenceVerify($numLockTime->getInt());
    }

    /**
     * @param ScriptInterface $script
     * @return CheckSequenceVerify
     */
    public static function fromScript(ScriptInterface $script): self
    {
        return static::fromDecodedScript($script->getScriptParser()->decode());
    }

    /**
     * @return int
     */
    public function getRelativeLockTime(): int
    {
        return $this->relativeTimeLock;
    }

    /**
     * @return bool
     */
    public function isRelativeToBlock(): bool
    {
        if ($this->isDisabled()) {
            throw new \RuntimeException("This opcode seems to be disabled");
        }

        return ($this->relativeTimeLock & TransactionInput::SEQUENCE_LOCKTIME_TYPE_FLAG) === 0;
    }

    /**
     * @return bool
     */
    public function isDisabled(): bool
    {
        return ($this->relativeTimeLock & TransactionInput::SEQUENCE_LOCKTIME_DISABLE_FLAG) != 0;
    }
}
