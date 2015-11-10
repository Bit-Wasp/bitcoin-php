<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptHashInfo\Multisig;
use BitWasp\Bitcoin\Script\ScriptHashInfo\PayToPubkey;
use BitWasp\Bitcoin\Script\ScriptHashInfo\PayToPubkeyHash;
use BitWasp\Bitcoin\Script\ScriptHashInfo\ScriptInfoInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;

class ScriptHash implements ScriptInfoInterface
{
    /**
     * @var ScriptInterface
     */
    private $redeemScript;

    /**
     * @var ScriptInterface
     */
    private $outputScript;

    /**
     * @var \BitWasp\Bitcoin\Address\ScriptHashAddress
     */
    private $p2shAddress;

    /**
     * @var ScriptInfoInterface
     */
    private $handler;

    /**
     * @param ScriptInterface $redeemScript
     */
    public function __construct(ScriptInterface $redeemScript)
    {
        $classifier = ScriptFactory::scriptPubKey()->classify($redeemScript);
        if ($classifier->isPayToScriptHash()) {
            throw new \InvalidArgumentException('Provided script is a pay-to-script-hash output script');
        }

        switch ($classifier->classify()) {
            case OutputClassifier::MULTISIG:
                $handler = new Multisig($redeemScript);
                break;
            case OutputClassifier::PAYTOPUBKEY:
                $handler = new PayToPubkey($redeemScript);
                break;
            case OutputClassifier::PAYTOPUBKEYHASH:
                $handler = new PayToPubkeyHash($redeemScript);
                break;
            default:
                throw new \InvalidArgumentException('redeemScript not yet supported');
        }

        $this->redeemScript = $redeemScript;
        $this->outputScript = ScriptFactory::scriptPubKey()->payToScriptHash($redeemScript);
        $this->p2shAddress = AddressFactory::fromScript($redeemScript);
        $this->handler = $handler;
    }

    /**
     * @return ScriptInterface
     */
    public function getOutputScript()
    {
        return $this->outputScript;
    }

    /**
     * @return \BitWasp\Bitcoin\Address\ScriptHashAddress
     */
    public function getAddress()
    {
        return $this->p2shAddress;
    }

    /**
     * @return int
     */
    public function getRequiredSigCount()
    {
        return $this->handler->getRequiredSigCount();
    }

    /**
     * @return int
     */
    public function getKeyCount()
    {
        return $this->handler->getKeyCount();
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @return bool
     */
    public function checkInvolvesKey(PublicKeyInterface $publicKey)
    {
        return $this->handler->checkInvolvesKey($publicKey);
    }

    /**
     * @return string
     */
    public function classification()
    {
        return $this->handler->classification();
    }

    /**
     * @return \BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface[]
     */
    public function getKeys()
    {
        return $this->handler->getKeys();
    }
}
