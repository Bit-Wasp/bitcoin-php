<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\RedeemScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\SignatureHash;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;
use BitWasp\Bitcoin\Signature\TransactionSignatureFactory;
use BitWasp\Buffertools\Buffer;

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
    public function __construct(
        EcAdapterInterface $ecAdapter,
        ScriptInterface $outputScript,
        RedeemScript $redeemScript = null
    ) {
        $classifier = new OutputClassifier($outputScript);
        $this->scriptType = $this->prevOutType = $classifier->classify();

        // Reclassify if the output is P2SH, so we know how to sign it.
        if ($this->scriptType == OutputClassifier::PAYTOSCRIPTHASH) {
            if (null === $redeemScript) {
                throw new \InvalidArgumentException('Redeem script is required when output is P2SH');
            }
            $rsClassifier = new OutputClassifier($redeemScript);
            $this->scriptType = $rsClassifier->classify();
        }

        // Gather public keys from redeemScript / outputScript
        $this->ecAdapter = $ecAdapter;
        $this->redeemScript = $redeemScript;
        $this->prevOutScript = $outputScript;

        // According to scriptType, extract public keys
        $this->execForInputTypes(
            function () {
            // For pay to pub key hash - nothing useful in output script
                $this->publicKeys = [];
            },
            function () {
            // For pay to pub key - we can extract this from the output script
                $chunks = $this->prevOutScript->getScriptParser()->parse();
                $this->publicKeys = [PublicKeyFactory::fromHex($chunks[0]->getHex(), $this->ecAdapter)];
            },
            function () {
            // Multisig - refer to the redeemScript
                $this->publicKeys = $this->redeemScript->getKeys();
            }
        );
    }

    /**
     * @param callable $forPayToPubKeyHash
     * @param callable $forPayToPubKey
     * @param callable $forMultisig
     * @return mixed
     */
    private function execForInputTypes(callable $forPayToPubKeyHash, callable $forPayToPubKey, callable $forMultisig)
    {
        switch ($this->scriptType) {
            case OutputClassifier::PAYTOPUBKEYHASH:
                return $forPayToPubKeyHash();
            case OutputClassifier::PAYTOPUBKEY:
                return $forPayToPubKey();
            case OutputClassifier::MULTISIG:
                return $forMultisig();
            default:
                throw new \InvalidArgumentException('Unsupported script type');
        }
    }

    /**
     * @return RedeemScript
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
     * @return ScriptInterface
     */
    public function getPrevOutScript()
    {
        return $this->prevOutScript;
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
     * @return array|\BitWasp\Bitcoin\Key\PublicKeyInterface[]
     */
    public function getPublicKeys()
    {
        return $this->publicKeys;
    }

    /**
     * @param TransactionSignatureInterface[] $signatures
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
     * @param integer $inputToExtract
     * @throws \Exception
     */
    public function extractSigs(TransactionInterface $tx, $inputToExtract)
    {
        $parsed = $tx
            ->getInputs()
            ->getInput($inputToExtract)
            ->getScript()
            ->getScriptParser()
            ->parse();

        $size = count($parsed);

        switch ($this->getScriptType()) {
            case OutputClassifier::PAYTOPUBKEYHASH:
                // Supply signature and public key in scriptSig
                if ($size == 2) {
                    $this->setSignatures([TransactionSignatureFactory::fromHex($parsed[0]->getHex(), $this->ecAdapter->getMath())]);
                    $this->setPublicKeys([PublicKeyFactory::fromHex($parsed[1]->getHex(), $this->ecAdapter)]);
                }

                break;
            case OutputClassifier::PAYTOPUBKEY:
                // Only has a signature in the scriptSig
                if ($size == 1) {
                    $this->setSignatures([TransactionSignatureFactory::fromHex($parsed[0]->getHex(), $this->ecAdapter->getMath())]);
                }

                break;
            case OutputClassifier::MULTISIG:
                $keys = $this->getRedeemScript()->getKeys();
                foreach ($keys as $idx => $key) {
                    $this->setSignature($idx, null);
                }

                if ($size > 2 && $size <= $this->getRedeemScript()->getKeyCount() + 2) {
                    $sigs = [];
                    foreach ($keys as $key) {
                        $sigs[$key->getPubKeyHash()->getHex()] = [];
                    }

                    // Extract Signatures (as buffers), then compile arrays of [pubkeyHash => signature]
                    $sigHash = new SignatureHash($tx);

                    foreach (array_slice($parsed, 1, -1) as $item) {
                        if ($item instanceof Buffer) {
                            $txSig = TransactionSignatureFactory::fromHex($item, $this->ecAdapter->getMath());
                            $linked = $this->ecAdapter->associateSigs(
                                [$txSig->getSignature()],
                                $sigHash->calculate(
                                    $this->getRedeemScript(),
                                    $inputToExtract,
                                    $txSig->getHashType()
                                ),
                                $this->getRedeemScript()->getKeys()
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
    }

    /**
     * @return \BitWasp\Bitcoin\Script\Script
     */
    public function regenerateScript()
    {
        // todo: this is worrisome, should have some way to fail and defer to the original script
        $signatures = array_filter($this->getSignatures());
        $script = $this->execForInputTypes(
            function () use (&$signatures) {
                return count($signatures) == 1
                    ? ScriptFactory::scriptSig()->payToPubKeyHash($signatures[0], $this->publicKeys[0])
                    : ScriptFactory::create();
            },
            function () use (&$signatures) {
                return count($signatures) == 1
                    ? ScriptFactory::scriptSig()->payToPubKey($signatures[0])
                    : ScriptFactory::create();
            },
            function () use (&$signatures) {
                return count($signatures) > 0
                    ? ScriptFactory::scriptSig()->multisigP2sh($this->getRedeemScript(), array_filter($this->signatures))
                    : ScriptFactory::create();
            }
        );

        return $script;
    }

    /**
     * @return int
     */
    public function getRequiredSigCount()
    {
        return $this->execForInputTypes(
            function () {
                return 1;
            },
            function () {
                return 1;
            },
            function () {
                return $this->redeemScript->getRequiredSigCount();
            }
        );
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
        // First check that public keys are set up as required, then
        // Compare the number of signatures with the required sig count
        return $this->execForInputTypes(
            function () {
                    return (count($this->publicKeys) == 1);
            },
            function () {
                    return (count($this->publicKeys) == 1);
            },
            function () {
                    return true;
            }
        ) && (count($this->signatures) == $this->getRequiredSigCount());
    }
}
