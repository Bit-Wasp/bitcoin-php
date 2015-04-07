<?php

namespace BitWasp\Bitcoin\Transaction;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\RedeemScript;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Signature\SignatureInterface;

/**
 * Class TransactionBuilder
 * @package BitWasp\Bitcoin\Transaction
 */
class TransactionBuilderInputState
{
    /**
     * @var null|string
     */
    private $hashType = null;

    /**
     * @var null|ScriptInterface
     */
    private $previousOutputScript = null;

    /**
     * @var null|string
     */
    private $previousOutputClassifier = null;

    /**
     * @var PublicKeyInterface[]
     */
    private $publicKeys = [];

    /**
     * @var null|string
     */
    private $scriptClassifier = null;

    /**
     * @var null|RedeemScript
     */
    private $redeemScript = null;

    /**
     * @var SignatureInterface[]
     */
    private $signatures = [];

    public function __construct()
    {

    }

    public function setSigHashType($sigHashType)
    {
        $this->hashType = $sigHashType;

        return $this;
    }

    public function getSigHashType()
    {
        return $this->hashType;
    }

    public function setPreviousOutputScript(ScriptInterface $script)
    {
        $this->previousOutputScript = $script;

        return $this;
    }

    public function getPreviousOutputScript()
    {
        return $this->previousOutputScript;
    }

    public function setPreviousOutputClassifier($classifier)
    {
        $this->previousOutputClassifier = $classifier;

        return $this;
    }

    /**
     * @TODO: could this just use $this->previousOutputScript ?
     *
     * @return null
     */
    public function getPreviousOutputClassifier()
    {
        return $this->previousOutputClassifier;
    }

    public function setPublicKeys($publicKeys)
    {
        $this->publicKeys = $publicKeys;

        return $this;
    }

    public function getPublicKeys()
    {
        return $this->publicKeys;
    }

    public function setRedeemScript(RedeemScript $redeemScript)
    {
        $this->redeemScript = $redeemScript;

        return $this;
    }

    public function getRedeemScript()
    {
        return $this->redeemScript;
    }

    public function setScriptType($scriptClassifier)
    {
        $this->scriptClassifier = $scriptClassifier;

        return $this;
    }

    public function getScriptType()
    {
        return $this->scriptClassifier;
    }

    public function getSignatures()
    {
        return $this->signatures;
    }

    public function setSignatures($signatures)
    {
        $this->signatures = $signatures;

        return $this;
    }

    public function setSignature($idx, SignatureInterface $signature)
    {
        $this->signatures[$idx] = $signature;

        return $this;
    }

    public function hasEnoughInfo()
    {
        return $this->hashType
            && $this->previousOutputScript
            && $this->previousOutputClassifier
            && count($this->publicKeys)
            && $this->scriptClassifier
            && count($this->signatures)
        ;
    }
}
