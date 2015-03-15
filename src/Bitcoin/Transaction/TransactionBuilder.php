<?php

namespace Afk11\Bitcoin\Transaction;

use Afk11\Bitcoin\Address\AddressInterface;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Key\PrivateKeyInterface;
use Afk11\Bitcoin\Script\Classifier\OutputClassifier;
use Afk11\Bitcoin\Script\RedeemScript;
use Afk11\Bitcoin\Script\ScriptFactory;
use Afk11\Bitcoin\Script\ScriptInterface;

class TransactionBuilder
{
    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @param TransactionInterface $tx
     */
    public function __construct(TransactionInterface $tx = null)
    {
        $this->transaction = $tx ?: TransactionFactory::create();
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
     * @param PrivateKeyInterface $priv
     * @param $inputToSign
     * @param RedeemScript $redeemScript
     * @return $this
     */
    public function signInputWithKey(PrivateKeyInterface $priv, $inputToSign, RedeemScript $redeemScript = null)
    {
        $input = $this->transaction->getInputs()->getInput($inputToSign);

        $prevOutput = $input->getPrevOutput();
        if ($prevOutput === null) {
            throw new \RuntimeException("PrevOutput not set for input $inputToSign");
        }

        $myPubKeyHash = $priv->getPubKeyHash();
        $outputScript = $prevOutput->getScript();
        $parse = $outputScript->parse();
        $prevOutType = new OutputClassifier($outputScript);

        if ($prevOutType->isPayToPublicKeyHash() && $parse[2] == $myPubKeyHash) {
            $signature = $this->transaction->sign($priv, $prevOutput, $inputToSign);
            $script = ScriptFactory::scriptSig()->payToPubKeyHash($signature, $priv->getPublicKey());

        } else if ($prevOutType->isPayToScriptHash() && $parse[1] == $redeemScript->getScriptHash()) {
            echo 'do p2sh';
            $output = new TransactionOutput($prevOutput->getValue(), $redeemScript);
            $signature = $this->transaction->sign($priv, $output, $inputToSign);
            $script = ScriptFactory::scriptSig()->multisigP2sh($redeemScript, array($signature), new Buffer());

        }

        if (isset($script)) {
            $this->transaction->getInputs()->getInput($inputToSign)->setScript($script);
        }

        return $this;
    }

    /**
     * @param PrivateKeyInterface $priv
     * @param RedeemScript $redeemScript
     * @return $this
     */
    public function signWithKey(PrivateKeyInterface $priv, RedeemScript $redeemScript = null)
    {
        foreach ($this->transaction->getInputs()->getInputs() as $c => $input) {
            $this->signInputWithKey($priv, $c, $redeemScript);
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
