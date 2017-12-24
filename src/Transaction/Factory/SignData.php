<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\WitnessScript;

class SignData
{
    /**
     * @var ScriptInterface
     */
    protected $redeemScript = null;

    /**
     * @var ScriptInterface
     */
    protected $witnessScript = null;

    /**
     * @var int
     */
    protected $signaturePolicy = null;

    /**
     * @var bool[]
     */
    protected $logicalPath = null;

    /**
     * @param ScriptInterface $redeemScript
     * @return $this
     */
    public function p2sh(ScriptInterface $redeemScript)
    {
        if ($redeemScript instanceof WitnessScript) {
            throw new \InvalidArgumentException("Cannot pass WitnessScript as a redeemScript");
        }
        $this->redeemScript = $redeemScript;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasRedeemScript(): bool
    {
        return $this->redeemScript instanceof ScriptInterface;
    }

    /**
     * @return ScriptInterface
     */
    public function getRedeemScript(): ScriptInterface
    {
        if (null === $this->redeemScript) {
            throw new \RuntimeException('Redeem script requested but not set');
        }

        return $this->redeemScript;
    }

    /**
     * @param ScriptInterface $witnessScript
     * @return $this
     */
    public function p2wsh(ScriptInterface $witnessScript)
    {
        $this->witnessScript = $witnessScript;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasWitnessScript(): bool
    {
        return $this->witnessScript instanceof ScriptInterface;
    }

    /**
     * @return ScriptInterface
     */
    public function getWitnessScript(): ScriptInterface
    {
        if (null === $this->witnessScript) {
            throw new \RuntimeException('Witness script requested but not set');
        }

        return $this->witnessScript;
    }
    
    /**
     * @param int $flags
     * @return $this
     */
    public function signaturePolicy(int $flags)
    {
        $this->signaturePolicy = $flags;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasSignaturePolicy(): bool
    {
        return $this->signaturePolicy !== null;
    }

    /**
     * @return int
     */
    public function getSignaturePolicy(): int
    {
        if (null === $this->signaturePolicy) {
            throw new \RuntimeException('Signature policy requested but not set');
        }
        return $this->signaturePolicy;
    }

    /**
     * @param bool[] $vfPathTaken
     * @return $this
     */
    public function logicalPath(array $vfPathTaken)
    {
        foreach ($vfPathTaken as $value) {
            if (!is_bool($value)) {
                throw new \RuntimeException("Invalid values for logical path, must be a boolean array");
            }
        }

        $this->logicalPath = $vfPathTaken;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasLogicalPath(): bool
    {
        return is_array($this->logicalPath);
    }

    /**
     * @return bool[]
     */
    public function getLogicalPath(): array
    {
        if (null === $this->logicalPath) {
            throw new \RuntimeException("Logical path requested but not set");
        }

        return $this->logicalPath;
    }
}
