<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Script\ScriptInterface;

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
     * @param ScriptInterface $redeemScript
     * @return $this
     */
    public function p2sh(ScriptInterface $redeemScript)
    {
        $this->redeemScript = $redeemScript;
        return $this;
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
     * @return ScriptInterface
     */
    public function getWitnessScript()
    {
        if (null === $this->witnessScript) {
            throw new \RuntimeException('Witness script requested but not set');
        }

        return $this->witnessScript;
    }
}
