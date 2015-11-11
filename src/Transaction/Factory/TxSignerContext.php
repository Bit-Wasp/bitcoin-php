<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\ScriptHash;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;
use BitWasp\Bitcoin\Signature\TransactionSignatureFactory;
use BitWasp\Bitcoin\Transaction\SignatureHash\Hasher;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;

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
     * @param ScriptInterface $redeemScript
     */
    public function __construct(
        EcAdapterInterface $ecAdapter,
        ScriptInterface $outputScript,
        ScriptInterface $redeemScript = null
    ) {
/*
        $classifier = new OutputClassifier($outputScript);
        $this->scriptType = $this->prevOutType = $classifier->classify();

        // Get the handler for this script type, and reclassify p2sh
        if ($this->scriptType === OutputClassifier::PAYTOSCRIPTHASH) {
            if (null === $redeemScript) {
                throw new \InvalidArgumentException('Redeem script is required when output is P2SH');
            }

            $handler = new ScriptHash($redeemScript);
            $this->scriptType = $handler->classification();
        } else {
            $handler = ScriptFactory::info($outputScript);
        }
*/
        $handler = ScriptFactory::info($outputScript, $redeemScript);
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
     * @param $publicKeys
     * @return $this
     */
    public function setPublicKeys($publicKeys)
    {
        $this->publicKeys = $publicKeys;

        return $this;
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
     * @param TransactionInterface $tx
     * @param $inputToExtract
     * @return $this
     */
    public function extractSigs(TransactionInterface $tx, $inputToExtract)
    {
        $inputs = $tx->getInputs();
        $parsed = $inputs[$inputToExtract]
            ->getScript()
            ->getScriptParser()
            ->parse();

        $size = count($parsed);

        switch ($this->getScriptType()) {
            case OutputClassifier::PAYTOPUBKEYHASH:
                // Supply signature and public key in scriptSig
                if ($size === 2) {
                    $this->signatures = [TransactionSignatureFactory::fromHex($parsed[0]->getHex(), $this->ecAdapter)];
                    $this->publicKeys = [PublicKeyFactory::fromHex($parsed[1]->getHex(), $this->ecAdapter)];
                }

                break;
            case OutputClassifier::PAYTOPUBKEY:
                // Only has a signature in the scriptSig
                if ($size === 1) {
                    $this->signatures = [TransactionSignatureFactory::fromHex($parsed[0]->getHex(), $this->ecAdapter)];
                }

                break;
            case OutputClassifier::MULTISIG:
                $redeemScript = $this->getRedeemScript();
                $keys = $this->scriptInfo->getKeys();
                foreach ($keys as $idx => $key) {
                    $this->setSignature($idx, null);
                }

                if ($size > 2 && $size <= $this->scriptInfo->getKeyCount() + 2) {
                    $sigs = [];
                    foreach ($keys as $key) {
                        $sigs[$key->getPubKeyHash()->getHex()] = [];
                    }

                    // Extract Signatures (as buffers), then compile arrays of [pubkeyHash => signature]
                    $sigHash = new Hasher($tx);

                    foreach (array_slice($parsed, 1, -1) as $item) {
                        if ($item instanceof Buffer) {
                            $txSig = TransactionSignatureFactory::fromHex($item, $this->ecAdapter);
                            $linked = $this->ecAdapter->associateSigs(
                                [$txSig->getSignature()],
                                $sigHash->calculate(
                                    $redeemScript,
                                    $inputToExtract,
                                    $txSig->getHashType()
                                ),
                                $keys
                            );

                            if (count($linked)) {
                                $key = array_keys($linked)[0];
                                $sigs[$key] = array_merge($sigs[$key], [$txSig]);
                            }
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
