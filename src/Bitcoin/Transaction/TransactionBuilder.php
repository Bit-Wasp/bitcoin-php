<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Signature\Signature;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\RedeemScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Signature\SignatureFactory;
use BitWasp\Bitcoin\Signature\SignatureCollection;
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
     * Contains msg32's of p2sh transactions, required to sort signatures
     * @var array
     */
    private $txHash = [];

    /**
     * @var integer[]
     */
    private $classification = [];

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
            ->addInput(new TransactionInput($tx->getTransactionId(), $outputToSpend));

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
        $this->transaction->getOutputs()->addOutput(new TransactionOutput(
            $value,
            ScriptFactory::scriptPubKey()->payToAddress($address)
        ));
        return $this;
    }

    /**
     * @param $forInput
     * @throws \Exception
     */
    private function extractSigs($forInput)
    {
        // Todo: should wrap in a try {} since this is where we deal with data from outside?
        //  - fromHex() functions can fail hard, from input that mightnt be safe to rely on.
        //  - should it die here, or should it just fail to find a signature, and regenerate with an empty script?

        if (false === isset($this->inputSigs[$forInput])) {
            $outputScript = $this->outputScript[$forInput];
            $outParse = $outputScript->getScriptParser()->parse();
            $inParse = $this->transaction->getInputs()->getInput($forInput)->getScript()->getScriptParser()->parse();
            $sSize = count($inParse);

            // Parse a SignatureCollection from the script, based on the outputScript
            $this->inputSigs[$forInput] = new TransactionSignatureCollection();

            switch ($this->getClassification($forInput)) {
                case OutputClassifier::PAYTOPUBKEYHASH:
                    if ($sSize == 2) {
                        // TODO: TransactionSignatureCollection - so pass a TransactionSignature
                        // ScriptSig: [vchSig] [vchPubKey]
                        // ScriptPubKey: OP_DUP OP_HASH160 0x14 [hash] OP_EQUALVERIFY OP_CHECKSIG
                        $this->addSignature($forInput, SignatureFactory::fromHex($inParse[0], $this->ecAdapter->getMath()));
                        $this->addPublicKey($forInput, PublicKeyFactory::fromHex($inParse[1]));
                    }

                    break;
                case OutputClassifier::PAYTOPUBKEY:
                    if ($sSize == 1) {
                        // TODO: TransactionSignatureCollection - so pass a TransactionSignature
                        // ScriptSig: [vchSig] [vchPubKey]
                        // ScriptPubKey: [vchPubKey] OP_CHECKSIG
                        $this->addSignature($forInput, SignatureFactory::fromHex($inParse[0], $this->ecAdapter->getMath()));
                        $this->addPublicKey($forInput, PublicKeyFactory::fromHex($outParse[0]));
                    }

                    break;
                case OutputClassifier::PAYTOSCRIPTHASH:
                case OutputClassifier::MULTISIG:
                    // TODO: TransactionSignatureCollection - so pass a TransactionSignature
                    // ScriptSig: OP_0 vector<vchSig> [redeemScript]
                    // ScriptPubKey:: OP_HASH160 0x14 [hash] OP_EQUAL
                    if (!isset($this->redeemScript[$forInput])) {
                        throw new \Exception('Must pass message hash / redeemScript to parse signatures');
                    }

                    $redeemScript = $this->redeemScript[$forInput];
                    $script = end($inParse);

                    // Matches, and there is at least one signature.
                    if ($script !== $redeemScript->getHex() || $sSize < 3) {
                        break;
                    }

                    // Associate a collection of signatures with their public keys
                    foreach(array_slice($inParse, 1, -2) as $buffer) {
                        if ($buffer instanceof Buffer) {
                            $sig = SignatureFactory::fromHex($buffer->getHex());
                            $this->addSignature($forInput, $sig);
                        }
                    }

                    // Extract public keys, and signatures
                    foreach ($redeemScript->getKeys() as $key) {
                        $this->addPublicKey($forInput, $key);
                    }

                    break;
            }

            $this->addOutputScript($forInput, $outputScript);

        }
    }

    /**
     * @param $forInput
     * @return \BitWasp\Bitcoin\Script\Script
     */
    private function regenerateScript($forInput)
    {
        if (!isset($this->inputStates[$forInput])) {
            return;
        }

        $inputState = $this->inputStates[$forInput];

        if ($inputState->getScriptType() === OutputClassifier::MULTISIG) {
            $signatures = array_filter($inputState->getSignatures());

            return ScriptFactory::scriptSig()->multisigP2sh($inputState->getRedeemScript(), $signatures);
        } else {
            throw new \RuntimeException("Not implemented");
        }



        if (false === isset($this->inputSigs[$forInput])) {
            return $this->transaction->getInputs()->getInput($forInput)->getScript();
        }

        switch ($this->classification[$forInput]) {
            case OutputClassifier::PAYTOPUBKEYHASH:
                $script = ScriptFactory::scriptSig()->payToPubKeyHash($this->inputSigs[$forInput]->getSignature(0), $this->publicKeys[$forInput][0]);
                break;
            case OutputClassifier::PAYTOPUBKEY:
                $script = ScriptFactory::scriptSig()->payToPubKey($this->inputSigs[$forInput][0], $this->publicKeys[$forInput][0]);
                break;
            case OutputClassifier::PAYTOSCRIPTHASH:
            case OutputClassifier::MULTISIG:
                // Todo: separate P2SH / multisig cases, and resolve dependency on txHash.
                $script = ScriptFactory::scriptSig()->multisigP2sh($this->redeemScript[$forInput], $this->inputSigs[$forInput], $this->txHash[$forInput]);
                break;
            default:
                // No idea how to classify this input!
                // Should we defer to $this->transaction->getInputs()->getInput($forInput)->getScript() like above?
                $script = ScriptFactory::create();
                break;
        }

        return $script;
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

        $this->transaction->getInputs()->getInput($inputToSign);

        if (!isset($this->inputStates[$inputToSign])) {
            $inputState = new TransactionBuilderInputState(
                $outputScript,
                $redeemScript,
                $sigHashType
                /* @TODO: maybe feed it some stuff in the constructor? */
            );
        } else {
            $inputState = $this->inputStates[$inputToSign];
        }

        if (!$inputState->hasEnoughInfo()) {

            // must be p2sh if a redeemscript was given
            if ($redeemScript) {
                $classifier = new OutputClassifier($redeemScript);

                $publicKeys = [];

                // @TODO: use a switch on $classifier->classify here?
                if ($classifier->isMultisig()) {
                    $publicKeys = $redeemScript->getKeys();

                } else if ($classifier->isPayToPublicKeyHash()) {
                    throw new \LogicException("Not implemented");
                } else if ($classifier->isPayToPublicKey()) {
                    throw new \LogicException("Not implemented");
                } else if ($classifier->isPayToScriptHash()) {
                    throw new \LogicException("Not implemented");
                } else {
                    throw new \InvalidArgumentException();
                }

                if (!$inputState->getPreviousOutputScript()) {
                    $inputState->setPreviousOutputScript($redeemScript);
                    $inputState->setPreviousOutputClassifier(OutputClassifier::PAYTOSCRIPTHASH);
                }

                $inputState->setPublicKeys($publicKeys);
                $inputState->setScriptType($classifier->classify());

            } else {
                if ($inputState->getScriptType()) {

                } else {
                    $inputState->setPreviousOutputScript(ScriptFactory::scriptPubKey()->payToAddress($privateKey->getAddress()));
                    $inputState->setPreviousOutputClassifier(OutputClassifier::PAYTOPUBKEYHASH);
                    $inputState->setPublicKeys([$privateKey->getPublicKey()]);
                    $inputState->setScriptType(/* @TODO: something */);
                }
            }

            $this->inputStates[$inputToSign] = $inputState;
        }

        $signatureHash = $this->transaction->signatureHash();

        if ($inputState->getPreviousOutputClassifier() == OutputClassifier::MULTISIG && !$inputState->getRedeemScript()) {
            throw new \RuntimeException("Can't sign multisig transaction without redeemscript");
        }

        $hash = $signatureHash->calculate($redeemScript ?: $outputScript, $inputToSign, $sigHashType);

        // for multisig we want signatures to be in the order of the publicKeys, so if it's not pre-filled OP_Os we're gonna do that now
        if ($inputState->getPreviousOutputClassifier() == OutputClassifier::MULTISIG && $inputState->getRedeemScript()
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
            if ($newScript = $this->regenerateScript($i)) {
                var_dump($newScript->getAsm());
                $transaction->getInputs()->getInput($i)->setScript($newScript);
            }
        }

        return $transaction;
    }
}
