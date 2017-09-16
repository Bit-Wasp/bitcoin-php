<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Locktime;
use BitWasp\Bitcoin\Script\ScriptInterface;

class SignData
{
    // Todo: review for useful exception?

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
     * @var int
     */
    protected $cltvTime = null;

    /**
     * @param ScriptInterface $redeemScript
     * @return $this
     */
    public function p2sh(ScriptInterface $redeemScript)
    {
        $this->redeemScript = $redeemScript;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasRedeemScript()
    {
        return $this->redeemScript instanceof ScriptInterface;
    }

    /**
     * @return ScriptInterface
     */
    public function getRedeemScript()
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
    public function hasWitnessScript()
    {
        return $this->witnessScript instanceof ScriptInterface;
    }

    /**
     * @return ScriptInterface
     */
    public function getWitnessScript()
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
    public function signaturePolicy($flags)
    {
        $this->signaturePolicy = $flags;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasSignaturePolicy()
    {
        return $this->signaturePolicy !== null;
    }

    /**
     * @return int
     */
    public function getSignaturePolicy()
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
    public function hasLogicalPath()
    {
        return is_array($this->logicalPath);
    }

    /**
     * @return bool[]
     */
    public function getLogicalPath()
    {
        if (null === $this->logicalPath) {
            throw new \RuntimeException("Logical path requested but not set");
        }

        return $this->logicalPath;
    }

    /**
     * @return int
     */
    public function getCltvTime() {
        if (null === $this->cltvTime) {
            throw new \RuntimeException("CLTV time requested but not set");
        }
        return $this->cltvTime;
    }

    /**
     * @return bool
     */
    public function hasCltvTime()
    {
        return is_int($this->cltvTime);
    }

    /**
     * @param int $time
     * @return $this
     */
    public function setCltvTime($time)
    {
        if (!is_int($time)) {
            throw new \RuntimeException("CLTV time must be an integer");
        }

        if ($time < 0 || $time > Locktime::TIME_MAX) {
            throw new \RuntimeException("CLTV time is out of range");
        }

        $this->cltvTime = $this;
        return $this;
    }
}
