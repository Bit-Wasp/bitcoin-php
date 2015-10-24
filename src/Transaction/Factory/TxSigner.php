<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Exceptions\BuilderNoInputState;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\RedeemScript;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Transaction\Mutator\InputMutator;
use BitWasp\Bitcoin\Transaction\Mutator\TxMutator;
use \BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter as Secp256k1Adapter;
use BitWasp\Bitcoin\Transaction\SignatureHash\SignatureHashInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;

class TxSigner
{
    /**
     * @var TransactionInterface
     */
    private $transaction;

    /**
     * @var bool
     */
    private $deterministicSignatures = true;

    /**
     * @var TxSignerContext[]
     */
    private $inputStates = [];

    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param TransactionInterface $tx
     */
    public function __construct(EcAdapterInterface $ecAdapter, TransactionInterface $tx)
    {
        $this->transaction = $tx;
        $this->ecAdapter = $ecAdapter;
    }

    /**
     * @return $this
     */
    public function useRandomSignatures()
    {
        if ($this->ecAdapter instanceof Secp256k1Adapter) {
            throw new \RuntimeException('Secp256k1 extension does not yet support random signatures');
        }

        $this->deterministicSignatures = false;
        return $this;
    }

    /**
     * @return $this
     */
    public function useDeterministicSignatures()
    {
        $this->deterministicSignatures = true;
        return $this;
    }

    /**
     * @param PrivateKeyInterface $privKey
     * @param Buffer $hash
     * @param $sigHashType
     * @return TransactionSignature
     */
    public function makeSignature(PrivateKeyInterface $privKey, Buffer $hash, $sigHashType)
    {
        return new TransactionSignature(
            $this->ecAdapter,
            $this->ecAdapter->sign(
                $hash,
                $privKey,
                $this->deterministicSignatures
                ? new Rfc6979(
                    $this->ecAdapter,
                    $privKey,
                    $hash,
                    'sha256'
                )
                : new Random()
            ),
            $sigHashType
        );
    }

    /**
     * @param $input
     * @return TxSignerContext
     * @throws BuilderNoInputState
     */
    public function inputState($input)
    {
        $this->transaction->getInputs()->get($input);
        if (!isset($this->inputStates[$input])) {
            throw new BuilderNoInputState('State not found for this input');
        }

        return $this->inputStates[$input];
    }

    /**
     * @param integer $inputToSign
     * @param ScriptInterface $outputScript
     * @param RedeemScript $redeemScript
     * @return TxSignerContext
     */
    private function createInputState($inputToSign, $outputScript, RedeemScript $redeemScript = null)
    {
        $state = (new TxSignerContext($this->ecAdapter, $outputScript, $redeemScript))
            ->extractSigs($this->transaction, $inputToSign);

        $this->inputStates[$inputToSign] = $state;

        return $state;
    }

    /**
     * @param $inputToSign
     * @param PrivateKeyInterface $privateKey
     * @param ScriptInterface $outputScript
     * @param RedeemScript $redeemScript
     * @param int $sigHashType
     * @return $this
     */
    public function sign(
        $inputToSign,
        PrivateKeyInterface $privateKey,
        ScriptInterface $outputScript,
        RedeemScript $redeemScript = null,
        $sigHashType = SignatureHashInterface::SIGHASH_ALL
    ) {
        // If the input state hasn't been set up, do so now.
        try {
            $inputState = $this->inputState($inputToSign);
        } catch (BuilderNoInputState $e) {
            $inputState = $this->createInputState($inputToSign, $outputScript, $redeemScript);
        }

        // If it's PayToPubkey / PayToPubkeyHash, TransactionBuilderInputState needs to know the public key.
        if (in_array($inputState->getPrevOutType(), [OutputClassifier::PAYTOPUBKEYHASH])) {
            $inputState->setPublicKeys([$privateKey->getPublicKey()]);
        }

        // loop over the publicKeys to find the key to sign with
        foreach ($inputState->getPublicKeys() as $idx => $publicKey) {
            if ($privateKey->getPublicKey()->getBinary() === $publicKey->getBinary()) {
                $inputState->setSignature(
                    $idx,
                    $this->makeSignature(
                        $privateKey,
                        $this->transaction
                            ->getSignatureHash()
                            ->calculate($redeemScript ?: $outputScript, $inputToSign, $sigHashType),
                        $sigHashType
                    )
                );
            }
        }

        return $this;
    }

    /**
     * @return TransactionInterface
     */
    public function get()
    {
        $inCount = count($this->transaction->getInputs());
        $mutator = new TxMutator($this->transaction);
        $inputs = $mutator->inputsMutator();

        for ($i = 0; $i < $inCount; $i++) {
            // Call regenerateScript if inputState is set, otherwise defer to previous script.
            try {
                $script = $this->inputState($i)->regenerateScript();
            } catch (BuilderNoInputState $e) {
                $script = $this->transaction->getInputs()->get($i)->getScript();
            }

            $inputs->applyTo($i, function (InputMutator $m) use ($script) {
                $m->script($script);
            });
        }

        return $mutator->get();
    }

    /**
     * @return bool
     */
    public function isFullySigned()
    {
        $transaction = $this->transaction;
        $inCount = count($transaction->getInputs());

        $total = 0;
        $signed = 0;
        for ($i = 0; $i < $inCount; $i++) {
            if (isset($this->inputStates[$i])) {
                /** @var TxSignerContext $state */
                $state = $this->inputStates[$i];
                $total += $state->getRequiredSigCount();
                $signed += $state->getSigCount();
            }
        }

        return $signed == $total;
    }
}
