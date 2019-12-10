<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Factory\ScriptInfo;

use BitWasp\Bitcoin\Locktime;
use BitWasp\Bitcoin\Script\Interpreter\Number;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\ScriptInterface;

class CheckLocktimeVerify
{
    /**
     * @var int
     */
    private $nLockTime;

    /**
     * @var bool
     */
    private $toBlock;

    /**
     * CheckLocktimeVerify constructor.
     * @param int $nLockTime
     */
    public function __construct(int $nLockTime)
    {
        if ($nLockTime < 0) {
            throw new \RuntimeException("locktime cannot be negative");
        }

        if ($nLockTime > Locktime::INT_MAX) {
            throw new \RuntimeException("nLockTime exceeds maximum value");
        }

        $this->nLockTime = $nLockTime;
        $this->toBlock = (new Locktime())->isLockedToBlock($nLockTime);
    }

    /**
     * @param Operation[] $chunks
     * @param bool $fMinimal
     * @return CheckLocktimeVerify
     */
    public static function fromDecodedScript(array $chunks, bool $fMinimal = false): CheckLocktimeVerify
    {
        if (count($chunks) !== 3) {
            throw new \RuntimeException("Invalid number of items for CLTV");
        }

        if (!$chunks[0]->isPush()) {
            throw new \InvalidArgumentException('CLTV script had invalid value for time');
        }

        if ($chunks[1]->getOp() !== Opcodes::OP_CHECKLOCKTIMEVERIFY) {
            throw new \InvalidArgumentException('CLTV script invalid opcode');
        }

        if ($chunks[2]->getOp() !== Opcodes::OP_DROP) {
            throw new \InvalidArgumentException('CLTV script invalid opcode');
        }

        $numLockTime = Number::buffer($chunks[0]->getData(), $fMinimal, 5);

        return new CheckLocktimeVerify($numLockTime->getInt());
    }

    /**
     * @param ScriptInterface $script
     * @return CheckLocktimeVerify
     */
    public static function fromScript(ScriptInterface $script): self
    {
        return static::fromDecodedScript($script->getScriptParser()->decode());
    }

    /**
     * @return int
     */
    public function getLocktime(): int
    {
        return $this->nLockTime;
    }

    /**
     * @return bool
     */
    public function isLockedToBlock(): bool
    {
        return $this->toBlock;
    }
}
