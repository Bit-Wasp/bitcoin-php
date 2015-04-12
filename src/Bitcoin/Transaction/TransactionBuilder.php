<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\RedeemScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Signature\SignatureHashInterface;

/**
 * Class TransactionBuilder
 * @package BitWasp\Bitcoin\Transaction
 */
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
        $this->transaction = $tx ?: TransactionFactory::create();
        $this->ecAdapter = $ecAdapter;
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
        // Check TransactionOutput exists
        $tx->getOutputs()->getOutput($outputToSpend);

        $this->transaction
            ->getInputs()
            ->addInput(new TransactionInput(
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
        $this->transaction
            ->getOutputs()
            ->addOutput(new TransactionOutput(
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
     * @return \BitWasp\Bitcoin\Signature\Signature
     */
    public function sign(PrivateKeyInterface $privKey, Buffer $hash)
    {
        $random = ($this->deterministicSignatures
            ? new Rfc6979($this->ecAdapter->getMath(), $this->ecAdapter->getGenerator(), $privKey, $hash, 'sha256')
            : new Random());

        return $this->ecAdapter->sign($hash, $privKey, $random);
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
        ScriptInterface $outputScript,
        $inputToSign,
        RedeemScript $redeemScript = null,
        $sigHashType = SignatureHashInterface::SIGHASH_ALL
    ) {

        $input = $this->transaction->getInputs()->getInput($inputToSign);

        if (!isset($this->inputStates[$inputToSign])) {
            $this->inputStates[$inputToSign] = new TransactionBuilderInputState($this->ecAdapter, $outputScript, $redeemScript);
            $this->inputStates[$inputToSign]->extractSigs($this->transaction, $inputToSign, $input->getScript());
        }

        $inputState = $this->inputStates[$inputToSign];
        $signatureHash = $this->transaction->signatureHash();
        $hash = $signatureHash->calculate($redeemScript ?: $outputScript, $inputToSign, $sigHashType);

        // Could this be done in TransactionBuilderInputState ?
        // for multisig we want signatures to be in the order of the publicKeys, so if it's not pre-filled OP_Os we're gonna do that now
        if ($inputState->getScriptType() == OutputClassifier::MULTISIG
            && count($inputState->getPublicKeys()) !== count($inputState->getSignatures())) {
            // this can be optimized by not checking against signatures we've already found
            $orderedSignatures = [];
            foreach ($inputState->getPublicKeys() as $idx => $publicKey) {
                $match = false;

                foreach ($inputState->getSignatures() as $signature) {
                    if ($this->ecAdapter->verify($hash, $publicKey, $signature)) {
                        $match = $signature;
                        break;
                    }
                }

                $orderedSignatures[] = $match ?: null;
            }

            $inputState->setSignatures($orderedSignatures);
        }

        // loop over the publicKeys so we can figure out in which order our signature needs to appear
        foreach ($inputState->getPublicKeys() as $idx => $publicKey) {
            if ($privateKey->getPublicKey()->getBinary() === $publicKey->getBinary()) {
                $inputState->setSignature($idx, $this->sign($privateKey, $hash));
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
            $caller = isset($this->inputStates[$i])
                ? function ($i) {
                    return $this->inputStates[$i]->regenerateScript();
                }
                : function ($i) {
                    return $this->transaction->getInputs()->getInput($i)->getScript();

                };

            $transaction->getInputs()->getInput($i)->setScript($caller($i));
        }

        return $transaction;
    }
}
