<?php

namespace Afk11\Bitcoin\Transaction;

use Afk11\Bitcoin\Address\AddressInterface;
use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Key\PrivateKeyInterface;
use Afk11\Bitcoin\Network\NetworkInterface;
use Afk11\Bitcoin\Script\Classifier\OutputClassifier;
use Afk11\Bitcoin\Script\ScriptFactory;

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
     * @param TransactionInterface $tx
     * @param $outputToSpend
     * @return $this
     */
    public function spendOutput(TransactionInterface $tx, $outputToSpend)
    {
        $output = $tx->getOutputs()->getOutput($outputToSpend);

        $input = (new TransactionInput())
            ->setTransactionId($tx->getTransactionId())
            ->setVout($outputToSpend)
            ->setPrevOutput($output);

        $this->transaction->getInputs()->addInput($input);

        return $this;
    }

    /**
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
     * @param PrivateKeyInterface $priv
     * @return $this
     */
    public function signWithKey(PrivateKeyInterface $priv)
    {
        $myPubKeyHash = $priv->getPubKeyHash();

        foreach ($this->transaction->getInputs()->getInputs() as $c => $input) {
            $prevOutput = $input->getPrevOutput();
            $outputScript = $prevOutput->getScript();
            $parse = $outputScript->parse();

            $prevOutType = new OutputClassifier($outputScript);

            if ($prevOutType->isPayToPublicKeyHash() && $parse[2] == $myPubKeyHash) {
                $signature = $this->transaction->sign($priv, $prevOutput, $c);
                $script = ScriptFactory::scriptSig()->payToPubKeyHash($signature, $priv->getPublicKey());
            }

            if (isset($script)) {
                $this->transaction->getInputs()->getInput($c)->setScript($script);
            }
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
