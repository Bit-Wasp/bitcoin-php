<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\RedeemScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Signature\SignatureCollection;
use BitWasp\Bitcoin\Signature\SignatureFactory;
use BitWasp\Bitcoin\Signature\SignatureInterface;
use BitWasp\Buffertools\Buffer;

/**
 * Class TransactionBuilder
 * @package BitWasp\Bitcoin\Transaction
 */
class TransactionBuilderInputState
{
    /**
     * @var null|RedeemScript
     */
    private $redeemScript;

    /**
     * @var ScriptInterface
     */
    private $prevOutScript;

    /**
     * @var string
     */
    private $prevOutType;

    /**
     * @var null|string
     */
    private $scriptType;

    /**
     * @var array
     */
    private $signatures = [];

    /**
     * @var PublicKeyInterface[]
     */
    private $publicKeys = [];

    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param ScriptInterface $outputScript
     * @param RedeemScript $redeemScript
     */
    public function __construct(EcAdapterInterface $ecAdapter, ScriptInterface $outputScript, RedeemScript $redeemScript = null)
    {
        $classifier = new OutputClassifier($outputScript);
        $inputScriptType = $outputType = $classifier->classify();

        // Reclassify if the output is P2SH, so we know how to sign it.
        if ($inputScriptType == OutputClassifier::PAYTOSCRIPTHASH) {
            if (null === $redeemScript) {
                throw new \InvalidArgumentException('Redeem script is required when output is P2SH');
            }
            $rsClassifier = new OutputClassifier($redeemScript);
            $inputScriptType = $rsClassifier->classify();
        }

        // Gather public keys from redeemScript / outputScript

        $publicKeys = [];
        switch ($inputScriptType) {
            case OutputClassifier::PAYTOPUBKEYHASH:
                break;
            case OutputClassifier::PAYTOPUBKEY:
                $chunks = $outputScript->getScriptParser()->parse();
                $publicKeys[] = PublicKeyFactory::fromHex($chunks[0]->getHex(), $ecAdapter);
                break;
            case OutputClassifier::MULTISIG:
                if (null === $redeemScript) {
                    throw new \InvalidArgumentException('Redeem script is required when output is multisig');
                }
                $publicKeys = $redeemScript->getKeys();
                break;
            default:
                throw new \InvalidArgumentException();
                break;
        }

        $this->redeemScript = $redeemScript;
        $this->prevOutScript = $outputScript;
        $this->prevOutType = $outputType;
        $this->scriptType = $inputScriptType;
        $this->publicKeys = $publicKeys;
    }

    /**
     * @return RedeemScript|null
     */
    public function getRedeemScript()
    {
        if (null === $this->redeemScript) {
            throw new \RuntimeException('This ');
        }
        return $this->redeemScript;
    }

    /**
     * @return ScriptInterface
     */
    public function getPrevOutScript()
    {
        return $this->prevOutScript;
    }

    /**
     * @TODO: could this just use $this->previousOutputScript ?
     * @todo: TK: either work I think - though calling isPayToScriptHash involves a computation instead of just comparing to OutputClassifier::PAYTOSCRIPTHASH
     *
     * @return string
     */
    public function getPrevOutType()
    {
        return $this->prevOutType;
    }

    /**
     * @return string
     */
    public function getScriptType()
    {
        return $this->scriptType;
    }

    /**
     * @param $publicKeys
     * @return $this
     */
    public function setPublicKeys($publicKeys)
    {
        $this->publicKeys = $publicKeys;

        return $this;
    }

    /**
     * @return array|\BitWasp\Bitcoin\Key\PublicKeyInterface[]
     */
    public function getPublicKeys()
    {
        return $this->publicKeys;
    }

    /**
     * @param SignatureInterface[] $signatures
     * @return $this
     */
    public function setSignatures($signatures)
    {
        $this->signatures = $signatures;
        return $this;
    }

    /**
     * @return array
     */
    public function getSignatures()
    {
        return $this->signatures;
    }

    /**
     * @param integer $idx
     * @param SignatureInterface $signature
     * @return $this
     */
    public function setSignature($idx, SignatureInterface $signature)
    {
        $this->signatures[$idx] = $signature;

        return $this;
    }

    /**
     * @param Transaction $tx
     * @param integer $inputToExtract
     * @param ScriptInterface $scriptSig
     * @throws \Exception
     */
    public function extractSigs(Transaction $tx, $inputToExtract, ScriptInterface $scriptSig)
    {
        $parsed = $scriptSig->getScriptParser()->parse();
        $size = count($parsed);

        switch ($this->getScriptType()) {
            case OutputClassifier::PAYTOPUBKEYHASH:
                // Supply signature and public key in scriptSig
                if ($size == 2) {
                    $this->setSignatures([SignatureFactory::fromHex($parsed[0]->getHex(), $this->ecAdapter->getMath())]);
                    $this->setPublicKeys([PublicKeyFactory::fromHex($parsed[1]->getHex(), $this->ecAdapter)]);
                }

                break;
            case OutputClassifier::PAYTOPUBKEY:
                // Only has a signature in the scriptSig
                if ($size == 1) {
                    $this->setSignatures([SignatureFactory::fromHex($parsed[0]->getHex(), $this->ecAdapter->getMath())]);
                }

                break;
            case OutputClassifier::MULTISIG:
                // Can't possibly be more than keyCount signatures, so restrict in this range
                if ($size > 2 && $size < $this->getRedeemScript()->getKeyCount() + 2) {
                    $sigs = [];
                    $keys = $this->getRedeemScript()->getKeys();
                    foreach ($keys as $key) {
                        $sigs[$key->getPubKeyHash()->getHex()] = [];
                    }

                    // Extract Signatures (as buffers), then compile arrays of [pubkeyHash => signature]
                    foreach (array_slice($parsed, 1, -1) as $item) {
                        if ($item instanceof Buffer) {
                            $sig = SignatureFactory::fromHex($parsed[0]->getHex(), $this->ecAdapter->getMath());
                            $linked = $this->ecAdapter->associateSigs(
                                new SignatureCollection([$sig]),
                                $tx
                                    ->signatureHash()
                                    ->calculate(
                                        $this->getPrevOutScript(),
                                        $inputToExtract,
                                        $sig->getSighashType()
                                    ),
                                $this->getRedeemScript()->getKeys()
                            );

                            $key = array_keys($linked)[0];
                            $sigs[$key] = array_merge($sigs[$key], $linked[$key]);
                        }
                    }

                    // We have all the signatures from the tx now. array_shift the sigs for a public key, as it's encountered.
                    foreach ($keys as $idx => $key) {
                        $hash = $key->getPubKeyHash()->getHex();
                        $this->setSignature($idx, isset($sigs[$hash])
                            ? array_shift($sigs[$hash])
                            : null);
                    }
                }

                break;
        }
    }

    /**
     * @return \BitWasp\Bitcoin\Script\Script
     */
    public function regenerateScript()
    {
        // todo: this is worrisome, should have some way to fail and defer to the original script
        $signatures = array_filter($this->getSignatures());
        switch ($this->getScriptType()) {
            case OutputClassifier::PAYTOPUBKEYHASH:
                $script = count($signatures) == 1
                    ? ScriptFactory::scriptSig()->payToPubKeyHash($signatures[0], $this->publicKeys[0])
                    : ScriptFactory::create();
                break;
            case OutputClassifier::PAYTOPUBKEY:
                $script = count($signatures) == 1
                    ? ScriptFactory::scriptSig()->payToPubKey($signatures[0])
                    : ScriptFactory::create();
                break;
            case OutputClassifier::MULTISIG:
                $script = count($signatures) > 0
                    ? ScriptFactory::scriptSig()->multisigP2sh($this->redeemScript, $signatures)
                    : ScriptFactory::create();
                break;
            default:
                $script = ScriptFactory::create();
                break;
        }

        return $script;
    }

    /**
     * @return bool
     */
    public function hasEnoughInfo()
    {
        return count($this->publicKeys)
            && count($this->signatures);
    }
}
