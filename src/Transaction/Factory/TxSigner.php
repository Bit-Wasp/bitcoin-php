<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Collection\Transaction\TransactionWitnessCollection;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Exceptions\BuilderNoInputState;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Transaction\Mutator\TxMutator;
use \BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter as Secp256k1Adapter;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\BufferInterface;

class TxSigner
{
    /**
     * @var TransactionInterface
     */
    private $transaction;

    /**
     * @var \BitWasp\Bitcoin\Transaction\SignatureHash\Hasher
     */
    private $signatureHash;

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
        $this->signatureHash = $tx->getSignatureHash();
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
     * @param BufferInterface $hash
     * @param int $sigHashType
     * @return TransactionSignature
     */
    public function makeSignature(PrivateKeyInterface $privKey, BufferInterface $hash, $sigHashType)
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
     * @param integer $input
     * @return TxSignerContext
     * @throws BuilderNoInputState
     */
    public function inputState($input)
    {
        $this->transaction->getInput($input);
        if (!array_key_exists($input, $this->inputStates)) {
            throw new BuilderNoInputState('State not found for this input');
        }

        return $this->inputStates[$input];
    }

    /**
     * @param int $inputToSign
     * @param ScriptInterface $outputScript
     * @param ScriptInterface|null $redeemScript
     * @return $this
     */
    private function createInputState($inputToSign, ScriptInterface $outputScript, ScriptInterface $redeemScript = null)
    {
        $state = (new TxSignerContext($this->ecAdapter, $outputScript, $redeemScript))->extractSigs($this->transaction, $inputToSign);

        $this->inputStates[$inputToSign] = $state;

        return $state;
    }

    /**
     * @param int $inputToSign
     * @param PrivateKeyInterface $privateKey
     * @param ScriptInterface $outputScript
     * @param ScriptInterface|null $redeemScript
     * @param int $sigHashType
     * @return $this
     */
    public function sign(
        $inputToSign,
        PrivateKeyInterface $privateKey,
        ScriptInterface $outputScript,
        ScriptInterface $redeemScript = null,
        $sigHashType = SigHash::ALL
    ) {
        // If the input state hasn't been set up, do so now.
        try {
            $inputState = $this->inputState($inputToSign);
        } catch (BuilderNoInputState $e) {
            $inputState = $this->createInputState($inputToSign, $outputScript, $redeemScript);
        }

        // If it's PayToPubkey / PayToPubkeyHash, TransactionBuilderInputState needs to know the public key.
        if ($inputState->getScriptType() === OutputClassifier::PAYTOPUBKEYHASH) {
            $inputState->setPublicKeys([$privateKey->getPublicKey()]);
        }

        // loop over the publicKeys to find the key to sign with
        foreach ($inputState->getPublicKeys() as $idx => $publicKey) {
            if ($privateKey->getPublicKey()->getBinary() === $publicKey->getBinary()) {
                $signature = $this->makeSignature(
                    $privateKey,
                    $this->signatureHash->calculate($redeemScript ?: $outputScript, $inputToSign, $sigHashType),
                    $sigHashType
                );

                //if ($inputState->getPrevOutType() === OutputClassifier::WITNESS) {
                //    $inputState->setWitness($idx, $signature);
                //} else {
                    $inputState->setSignature($idx, $signature);
                //}
            }
        }

        return $this;
    }

    /**
     * @return TransactionInterface
     */
    public function get()
    {
        $mutator = new TxMutator($this->transaction);
        $inputs = $mutator->inputsMutator();
        $txInputs = $this->transaction->getInputs();

        $i = 0;
        foreach ($inputs as $input) {
            // Call regenerateScript if inputState is set, otherwise defer to previous script.
            try {
                $script = $this->inputState($i)->regenerateScript();
            } catch (BuilderNoInputState $e) {
                $script = $txInputs[$i]->getScript();
            }

            $input->script($script);
            $i++;
        }

        $mutator->witness(new TransactionWitnessCollection([]));

        return $mutator->done();
    }

    /**
     * @return bool
     */
    public function isFullySigned()
    {
        foreach ($this->transaction->getInputs() as $i => $input) {
            if (array_key_exists($i, $this->inputStates)) {
                /** @var TxSignerContext $state */
                $state = $this->inputStates[$i];
                if (!$state->isFullySigned()) {
                    return false;
                }
            }
        }

        return true;
    }
}
