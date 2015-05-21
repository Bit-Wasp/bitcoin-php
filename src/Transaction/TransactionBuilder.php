<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Exceptions\BuilderNoInputState;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\RedeemScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;

class TransactionBuilder
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
     * @var TransactionBuilderInputState[]
     */
    private $inputStates = [];

    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param TransactionInterface $tx
     * @internal param Math $math
     * @internal param GeneratorPoint $generatorPoint
     */
    public function __construct(EcAdapterInterface $ecAdapter, TransactionInterface $tx = null)
    {
        $this->transaction = $tx ?: new Transaction();
        $this->ecAdapter = $ecAdapter;
    }

    /**
     * @param TransactionInputInterface $input
     * @return $this
     */
    public function addInput(TransactionInputInterface $input)
    {
        $this->transaction->getInputs()->addInput($input);
        return $this;
    }

    /**
     * @param TransactionOutputInterface $output
     * @return $this
     */
    public function addOutput(TransactionOutputInterface $output)
    {
        $this->transaction->getOutputs()->addOutput($output);
        return $this;
    }

    /**
     * @param Utxo $utxo
     * @return $this
     */
    public function spendUtxo(Utxo $utxo)
    {
        $this->addInput(new TransactionInput(
            $utxo->getTransactionId(),
            $utxo->getVout()
        ));

        return $this;
    }

    /**
     * Create an input for this transaction spending $tx's output, $outputToSpend.
     *
     * @param TransactionInterface $tx
     * @param $outputToSpend
     * @return $this
     */
    public function spendOutput(TransactionInterface $tx, $outputToSpend)
    {
        // Check TransactionOutput exists in $tx
        $tx->getOutputs()->getOutput($outputToSpend);
        $this->addInput(new TransactionInput(
            $tx->getTransactionId(),
            $outputToSpend
        ));

        return $this;
    }

    /**
     * Create an output paying $value to an Address.
     *
     * @param AddressInterface $address
     * @param $value
     * @return $this
     */
    public function payToAddress(AddressInterface $address, $value)
    {
        // Create Script from address, then create an output.
        $this->addOutput(new TransactionOutput(
            $value,
            ScriptFactory::scriptPubKey()->payToAddress($address)
        ));

        return $this;
    }

    /**
     * @return $this
     */
    public function useRandomSignatures()
    {
        if ($this->ecAdapter->getAdapterName() == EcAdapterInterface::SECP256K1) {
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
    public function sign(PrivateKeyInterface $privKey, Buffer $hash, $sigHashType)
    {
        return new TransactionSignature(
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
     * @return TransactionBuilderInputState
     * @throws BuilderNoInputState
     */
    public function getInputState($input)
    {
        $this->transaction->getInputs()->getInput($input);
        if (!isset($this->inputStates[$input])) {
            throw new BuilderNoInputState('State not found for this input');
        }

        return $this->inputStates[$input];
    }

    /**
     * @param integer $inputToSign
     * @param ScriptInterface $outputScript
     * @param RedeemScript $redeemScript
     * @return TransactionBuilderInputState
     */
    public function createInputState($inputToSign, $outputScript, RedeemScript $redeemScript = null)
    {
        $this->inputStates[$inputToSign] = new TransactionBuilderInputState(
            $this->ecAdapter,
            $outputScript,
            $redeemScript
        );

        $this->inputStates[$inputToSign]->extractSigs($this->transaction, $inputToSign);

        return $this->getInputState($inputToSign);
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param ScriptInterface $outputScript
     * @param $inputToSign
     * @param int $sigHashType
     * @param RedeemScript $redeemScript
     * @return $this
     * @throws \Exception
     */
    public function signInputWithKey(
        PrivateKeyInterface $privateKey,
        ScriptInterface $outputScript,
        $inputToSign,
        RedeemScript $redeemScript = null,
        $sigHashType = SignatureHashInterface::SIGHASH_ALL
    ) {
        // If the input state hasn't been set up, do so now.
        try {
            $inputState = $this->getInputState($inputToSign);
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
                    $this->sign(
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
     * @return Transaction
     */
    public function getTransaction()
    {
        $transaction = $this->transaction;
        $inCount = count($transaction->getInputs());

        for ($i = 0; $i < $inCount; $i++) {
            // Call regenerateScript if inputState is set, otherwise defer to previous script.
            try {
                $script = $this->getInputState($i)->regenerateScript();
            } catch (BuilderNoInputState $e) {
                $script = $this->transaction->getInputs()->getInput($i)->getScript();
            }

            $transaction->getInputs()->getInput($i)->setScript($script);
        }

        return $transaction;
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
                $total += $this->inputStates[$i]->getRequiredSigCount();
                $signed += $this->inputStates[$i]->getSigCount();
            }
        }

        return $signed == $total;
    }
}
