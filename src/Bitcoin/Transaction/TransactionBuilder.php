<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
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
     * @var SignatureCollection[]
     */
    private $inputSigs = [];

    /**
     * @var PublicKeyInterface[]
     */
    private $publicKeys = [];

    /**
     * @var ScriptInterface[]
     */
    private $outputScript = [];

    /**
     * @var RedeemScript[]
     */
    private $redeemScript = [];

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
     * @param PublicKeyInterface $publicKey
     * @return $this
     */
    private function addPublicKey($forInput, PublicKeyInterface $publicKey)
    {
        if (isset($this->publicKeys[$forInput])) {
            $this->publicKeys[$forInput][] = $publicKey;
        } else {
            $this->publicKeys[$forInput] = [$publicKey];
        }

        return $this;
    }

    /**
     * @param $forInput
     * @param TransactionSignature $txSig
     * @return $this
     */
    private function addSignature($forInput, TransactionSignature $txSig)
    {
        if (isset($this->inputSigs[$forInput])) {
            $this->inputSigs[$forInput]->addSignature($txSig);
        } else {
            $this->inputSigs[$forInput] = new TransactionSignatureCollection([$txSig]);
        }

        return $this;
    }

    /**
     * @param $forInput
     * @param ScriptInterface $outputScript
     * @return $this
     */
    private function addOutputScript($forInput, ScriptInterface $outputScript)
    {
        if (!isset($this->outputScript[$forInput])) {
            $this->outputScript[$forInput] = $outputScript;
        }

        return $this;
    }

    /**
     * @param $forInput
     * @param RedeemScript $redeemScript
     * @return $this
     */
    private function addRedeemScript($forInput, RedeemScript $redeemScript)
    {
        if (!isset($this->redeemScript[$forInput])) {
            $this->redeemScript[$forInput] = $redeemScript;
        }

        return $this;
    }

    /**
     * @param $forInput
     * @param ScriptInterface $script
     * @return $this
     */
    private function addClassification($forInput, ScriptInterface $script)
    {
        if (!isset($this->classification[$forInput])) {
            if ($script instanceof RedeemScript) {
                // Todo: revise.. or rename RedeemScript.
                $this->addRedeemScript($forInput, $script);
                $this->classification[$forInput] = OutputClassifier::MULTISIG;
                $this->outputScript[$forInput] = $script->getOutputScript();
            } else {
                $classifier = new OutputClassifier($script);
                $this->classification[$forInput] = $classifier->classify();
                $this->outputScript[$forInput] = $script;
            }
        }

        return $this;
    }

    /**
     * @param $forInput
     * @return int
     */
    private function getClassification($forInput)
    {
        return $this->classification[$forInput];
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
    public function sign(PrivateKeyInterface $privKey, Buffer $hash, $sigHashType)
    {
        $random = ($this->deterministicSignatures
            ? new Rfc6979($this->ecAdapter->getMath(), $this->ecAdapter->getGenerator(), $privKey, $hash, 'sha256')
            : new Random());

        return new TransactionSignature(
            $this->ecAdapter->sign($hash, $privKey, $random),
            $sigHashType
        );
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
        $prevOutType = $this->addClassification($inputToSign, $redeemScript ?: $outputScript)->getClassification($inputToSign);
        $parse = $outputScript->getScriptParser()->parse();
        $signatureHash = $this->transaction->signatureHash();
        $pubKeyHash = $privateKey->getPubKeyHash();

        if ($prevOutType == OutputClassifier::PAYTOPUBKEYHASH) {
            if ($parse[2]->getBinary() == $pubKeyHash->getBinary()) {
                $hash = $signatureHash->calculate($outputScript, $inputToSign, $sigHashType);
                $signatures = [$this->sign($privateKey, $hash, $sigHashType)];
            }

            // TODO: P2SH !== multisig, more work to be done here..
        } else if (in_array($prevOutType, [OutputClassifier::PAYTOSCRIPTHASH, OutputClassifier::MULTISIG])) {
            if (!isset($this->redeemScript[$inputToSign])) {
                throw new \Exception('Redeem script should be passed when signing a p2sh input');
            }

            if ($parse[1]->getBinary() == $redeemScript->getScriptHash()->getBinary()) {
                $signatures = [];
                $hash = $signatureHash->calculate($redeemScript, $inputToSign, $sigHashType);
                foreach ($this->redeemScript[$inputToSign]->getKeys() as $key) {
                    if ($pubKeyHash->getBinary() == $key->getPubKeyHash()->getBinary()) {
                        $signatures[] = $this->sign($privateKey, $hash, $sigHashType);
                        // todo: this is required for associating sigs with keys, and hence ordering signatures properly. how can it be avoided?
                        $this->txHash[$inputToSign] = $hash;
                    }
                }
            }
        } else {
            throw new \Exception('Unsupported transaction type');
        }

        if (isset($hash) && isset($signatures)) {
            // ExtractSigs is only run once per input.
            $this->extractSigs($inputToSign, $outputScript, $input->getScript(), $hash, $redeemScript);

            // Add TransactionSignatures we were able to create
            foreach ($signatures as $signature) {
                $this->addSignature($inputToSign, $signature);
            }

            $this->addPublicKey($inputToSign, $privateKey->getPublicKey());
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
            $newScript = $this->regenerateScript($i);
            $transaction->getInputs()->getInput($i)->setScript($newScript);
        }

        return $transaction;
    }
}
