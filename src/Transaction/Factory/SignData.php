<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

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
}
