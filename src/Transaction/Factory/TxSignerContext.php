<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\ScriptHash;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Signature\SignatureSort;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;
use BitWasp\Bitcoin\Signature\TransactionSignatureFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class TxSignerContext
{
    /**
     * @var \BitWasp\Bitcoin\Script\ScriptInfo\ScriptInfoInterface
     */
    private $scriptInfo;

    /**
     * @var null|ScriptInterface
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
     * @var string
     */
    private $scriptType;

    /**
     * @var TransactionSignatureInterface[]
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
     * TxSignerContext constructor.
     * @param EcAdapterInterface $ecAdapter
     * @param ScriptInterface $outputScript
     * @param ScriptInterface|null $redeemScript
     */
    public function __construct(
        EcAdapterInterface $ecAdapter,
        ScriptInterface $outputScript,
        ScriptInterface $redeemScript = null
    ) {
        $handler = ScriptFactory::info($outputScript, $redeemScript);
        $handler->getKeys();
        $this->scriptType = $this->prevOutType = $handler->classification();
        if ($handler instanceof ScriptHash) {
            $this->scriptType = $handler->getInfo()->classification();
        }

        // Gather public keys from redeemScript / outputScript
        $this->ecAdapter = $ecAdapter;
        $this->redeemScript = $redeemScript;
        $this->prevOutScript = $outputScript;
        $this->scriptInfo = $handler;

        // According to scriptType, extract public keys
        $this->publicKeys = $this->scriptInfo->getKeys();
    }

    /**
     * @return ScriptInterface
     * @throws \RuntimeException
     */
    public function getRedeemScript()
    {
        if (null === $this->redeemScript) {
            throw new \RuntimeException('No redeem script was set');
        }

        return $this->redeemScript;
    }

    /**
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
     * @return array|\BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface[]
     */
    public function getPublicKeys()
    {
        return $this->publicKeys;
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
     * @param TransactionSignatureInterface $signature
     * @return $this
     */
    public function setSignature($idx, TransactionSignatureInterface $signature = null)
    {
        $this->signatures[$idx] = $signature;
        return $this;
    }

    /**
     * @param PublicKeyInterface[] $publicKeys
     * @return $this
     */
    public function setPublicKeys(array $publicKeys)
    {
        $this->publicKeys = $publicKeys;
        return $this;
    }

    /**
     * @param TransactionInterface $tx
     * @param int $inputToExtract
     * @return $this
     */
    public function extractSigs(TransactionInterface $tx, $inputToExtract)
    {
        $parsed = $tx->getInput($inputToExtract)
            ->getScript()
            ->getScriptParser()
            ->decode();

        $size = count($parsed);

        switch ($this->getScriptType()) {
            case OutputClassifier::PAYTOPUBKEYHASH:
                // Supply signature and public key in scriptSig
                if ($size === 2) {
                    $this->signatures = [TransactionSignatureFactory::fromHex($parsed[0]->getData(), $this->ecAdapter)];
                    $this->publicKeys = [PublicKeyFactory::fromHex($parsed[1]->getData(), $this->ecAdapter)];
                }

                break;
            case OutputClassifier::PAYTOPUBKEY:
                // Only has a signature in the scriptSig
                if ($size === 1) {
                    $this->signatures = [TransactionSignatureFactory::fromHex($parsed[0]->getData(), $this->ecAdapter)];
                }

                break;
            case OutputClassifier::MULTISIG:
                $redeemScript = $this->getRedeemScript();
                $this->signatures = array_fill(0, count($this->publicKeys), null);

                if ($size > 2 && $size <= $this->scriptInfo->getKeyCount() + 2) {
                    $sigHash = $tx->getSignatureHash();
                    $sigSort = new SignatureSort($this->ecAdapter);
                    $sigs = new \SplObjectStorage;

                    foreach (array_slice($parsed, 1, -1) as $item) {
                        /** @var \BitWasp\Bitcoin\Script\Parser\Operation $item */
                        if ($item->isPush()) {
                            $txSig = TransactionSignatureFactory::fromHex($item->getData(), $this->ecAdapter);
                            $hash = $sigHash->calculate($redeemScript, $inputToExtract, $txSig->getHashType());
                            $linked = $sigSort->link([$txSig->getSignature()], $this->publicKeys, $hash);

                            foreach ($this->publicKeys as $key) {
                                if ($linked->contains($key)) {
                                    $sigs[$key] = $txSig;
                                }
                            }
                        }
                    }

                    // We have all the signatures from the input now. array_shift the sigs for a public key, as it's encountered.
                    foreach ($this->publicKeys as $idx => $key) {
                        $this->signatures[$idx] = isset($sigs[$key]) ? $sigs[$key] : null;
                    }
                }

                break;
        }

        return $this;
    }

    /**
     * @return \BitWasp\Bitcoin\Script\Script
     */
    public function regenerateScript()
    {
        $signatures = array_filter($this->getSignatures());
        return $this->scriptInfo->makeScriptSig($signatures, $this->publicKeys);
    }

    /**
     * @return int
     */
    public function getRequiredSigCount()
    {
        return $this->scriptInfo->getRequiredSigCount();
    }

    /**
     * @return int
     */
    public function getSigCount()
    {
        return count(array_filter($this->signatures));
    }

    /**
     * @return bool
     */
    public function isFullySigned()
    {
        // Compare the number of signatures with the required sig count
        return $this->getSigCount() === $this->getRequiredSigCount();
    }
}
