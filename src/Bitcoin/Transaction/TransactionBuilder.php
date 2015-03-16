<?php

namespace Afk11\Bitcoin\Transaction;

use Afk11\Bitcoin\Address\AddressInterface;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Crypto\Random\Random;
use Afk11\Bitcoin\Crypto\Random\Rfc6979;
use Afk11\Bitcoin\Key\PrivateKeyInterface;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\Script\Classifier\OutputClassifier;
use Afk11\Bitcoin\Script\RedeemScript;
use Afk11\Bitcoin\Script\ScriptFactory;
use Afk11\Bitcoin\Script\ScriptInterface;
use Afk11\Bitcoin\Signature\SignatureCollection;
use Afk11\Bitcoin\Signature\SignatureHashInterface;
use Afk11\Bitcoin\Signature\Signer;
use Mdanter\Ecc\GeneratorPoint;

class TransactionBuilder
{
    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var bool
     */
    private $deterministicSignatures = true;

    /**
     * @var SignatureCollection[]
     */
    private $inputSigs = [];

    /**
     * @param Math $math
     * @param GeneratorPoint $generatorPoint
     * @param TransactionInterface $tx
     */
    public function __construct(Math $math, GeneratorPoint $generatorPoint, TransactionInterface $tx = null)
    {
        $this->transaction = $tx ?: TransactionFactory::create();
        for ($i = 0; $i < $this->transaction->getInputs()->count(); $i++) {
            $this->inputSigs[$i] = new SignatureCollection;
        }
        $this->math = $math;
        $this->generator = $generatorPoint;
        $this->signer = new Signer($math, $generatorPoint);
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
        $output = $tx->getOutputs()->getOutput($outputToSpend);

        $input = new TransactionInput($tx->getTransactionId(), $outputToSpend);
        $input->setPrevOutput($output);

        $this->transaction->getInputs()->addInput($input);
        $this->inputSigs[count($this->transaction->getInputs()) - 1] = new SignatureCollection();
        return $this;
    }

    /**
     * Create an output paying $value to an Address.
     * @param AddressInterface $address
     * @param $value
     * @return $this
     */
    public function payToAddress(AddressInterface $address, $value)
    {
        $script = ScriptFactory::scriptPubKey()->payToAddress($address);
        $output = new TransactionOutput($value, $script);
        $this->transaction->getOutputs()->addOutput($output);

        return $this;
    }

    /**
     * @param ScriptInterface $script
     * @param $value
     * @return TransactionBuilder
     */
    public function payToScriptHash(ScriptInterface $script, $value)
    {
        return $this->payToAddress($script->getAddress(), $value);
    }

    /**
     * @return $this
     */
    public function useRandomSignatures()
    {
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
     * @return \Afk11\Bitcoin\Signature\Signature
     */
    public function sign(PrivateKeyInterface $privKey, Buffer $hash)
    {
        $random = ($this->deterministicSignatures
            ? new Rfc6979($this->math, $this->generator, $privKey, $hash, 'sha256')
            : new Random());
        return $this->signer->sign($privKey, $hash, $random);
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param $inputToSign
     * @param int $sigHashType
     * @param RedeemScript $redeemScript
     * @return $this
     * @throws \Exception
     */
    public function signInputWithKey(
        PrivateKeyInterface $privateKey,
        $inputToSign,
        RedeemScript $redeemScript = null,
        $sigHashType = SignatureHashInterface::SIGHASH_ALL
    ) {

        $input = $this->transaction->getInputs()->getInput($inputToSign);
        // Parse
        $output = $input->getPrevOutput();
        if ($output === null) {
            throw new \RuntimeException("PrevOutput was not set for input $inputToSign");
        }

        $prevOutType = new OutputClassifier($output->getScript());
        $parse = $output->getScript()->getScriptParser()->parse();
        $signatureHash = $this->transaction->signatureHash();

        if ($prevOutType->isPayToPublicKeyHash() && $parse[2] == $privateKey->getPubKeyHash()) {
            $hash = $signatureHash->calculate($output->getScript(), $inputToSign, $sigHashType);
            $signature = $this->sign($privateKey, $hash);
            $script = ScriptFactory::scriptSig()->payToPubKeyHash($signature, $privateKey->getPublicKey());
        } else if ($prevOutType->isPayToScriptHash() && $parse[1] == $redeemScript->getScriptHash()) {
            $hash = $signatureHash->calculate($redeemScript, $inputToSign, $sigHashType);
            $signature = $this->sign($privateKey, $hash);
            $script = ScriptFactory::scriptSig()->multisigP2sh($redeemScript, new SignatureCollection(array($signature)), $hash);
            // todo..
        }

        // Add and reserialize
        if (isset($signature)) {
            $this->transaction->getInputs()->getInput($inputToSign)->setScript($script);
        }

        return $this;
    }

    /**
     * @param PrivateKeyInterface $priv
     * @param RedeemScript $redeemScript
     * @return $this
     */
    public function signWithKey(PrivateKeyInterface $priv, RedeemScript $redeemScript = null, $sigHashType = SignatureHashInterface::SIGHASH_ALL)
    {
        foreach ($this->transaction->getInputs()->getInputs() as $c => $input) {
            $this->signInputWithKey($priv, $c, $redeemScript, $sigHashType);
        }

        return $this;
    }

    /**
     * @return Transaction
     */
    public function getTransaction()
    {
        return $this->transaction;
    }
}
