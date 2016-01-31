<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Collection\Transaction\TransactionWitnessCollection;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;

class TxWitnessSigner
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var TransactionInterface
     */
    private $tx;

    /**
     * @var SignatureData
     */
    private $signatures = [];

    /**
     * @var TxSigCreator
     */
    private $signatureCreator = [];

    /**
     * TxWitnessSigner constructor.
     * @param TransactionInterface $tx
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(TransactionInterface $tx, EcAdapterInterface $ecAdapter)
    {
        $this->tx = $tx;
        $this->ecAdapter = $ecAdapter;
        $nInputs = count($tx->getInputs());
        for ($i = 0; $i < $nInputs; $i++) {
            $this->signatures[] = new SignatureData();
        }
    }

    public function sign($nInput, $amount, PrivateKeyInterface $privateKey, ScriptInterface $scriptPubKey, ScriptInterface $redeemScript = null, ScriptInterface $witnessScript = null, $sigHashType = SigHash::ALL)
    {

        if (!isset($this->signatureCreator[$nInput])) {
            $this->signatureCreator[$nInput] = new TxSigCreator($this->ecAdapter, $this->tx, $nInput, $amount);
        }

        /** @var TxSigCreator $sigCreator */
        $sigCreator = $this->signatureCreator[$nInput];
        $sigData = $this->signatures[$nInput];
        $sigVersion = 0;
        $scriptSig = $this->tx->getInput($nInput)->getScript();
        $witness = null;

        $scriptCode = null;
        if ($sigData->scriptType === null) {
            $classifier = new OutputClassifier($scriptPubKey);
            $sigData->innerScriptType = $sigData->scriptType = $classifier->classify($sigData->solution);
            var_dump($sigData->innerScriptType);
        }

        if ($sigData->scriptType === OutputClassifier::PAYTOSCRIPTHASH) {
            $classifier = new OutputClassifier($redeemScript);
            $sigData->innerScriptType = $classifier->classify();
            $scriptCode = $redeemScript;
        }

        if ($sigData->innerScriptType === OutputClassifier::WITNESS_V0_KEYHASH) {
            $sigVersion = 1;
            $scriptPubKey->isWitness($witness);
            $witnessScript = ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $witness->getProgram(), Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]);
            $classifier = new OutputClassifier($witnessScript);
            $classifier->classify($sigData->solution);

            $sigData->witnessScript = $witnessScript;
            $scriptSig = new Script();
            $scriptCode = $witnessScript;
        }

        if ($sigData->innerScriptType === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            echo "Signing witness scripthash\n";
            $sigVersion = 1;
            $scriptPubKey->isWitness($witness);
            if (null === $witnessScript) {
                throw new \RuntimeException('Witness script is required for v0_scripthash');
            }

            $classifier = new OutputClassifier($witnessScript);
            $sigData->innerScriptType = $classifier->classify();
            echo "Heres some solution thing\n";
            print_r($sigData->solution);
            $sigData->witnessScript = $witnessScript;
            echo $witnessScript->getScriptParser()->getHumanReadable() . PHP_EOL;
            $scriptCode = $witnessScript;
        }

        if ($sigData->scriptType === OutputClassifier::UNKNOWN) {
            throw new \RuntimeException('Unsupported scriptPubKey');
        }

        if ($sigData->signatures === null) {
            $sigCreator->extractSignatures($sigData, $scriptSig, $redeemScript);
        }

        $sigCreator->signInput($sigData, $privateKey, $scriptCode ?: $scriptPubKey, $sigHashType, $sigVersion);

        return $this;
    }

    public function get()
    {
        $mutable = TransactionFactory::mutate($this->tx);
        $witnesses = [];
        foreach ($mutable->inputsMutator() as $idx => $input) {
            $sigData = $this->signatures[$idx];
            $sigCreator = $this->signatureCreator[$idx];
            $witness = null;
            $sig = $sigCreator->serializeSig($sigData, $witness);
            echo "ScriptSig: \n";
            echo $sig->getHex() . "\n";

            echo "Witness data\n";
            var_dump($witness);
            $input->script($sig);
            $witnesses[$idx] = $witness ?: new ScriptWitness([]);
        }

        if (count($witnesses) > 0) {
            $mutable->witness(new TransactionWitnessCollection($witnesses));
        }

        $new = $mutable->done();
        return $new;
    }
}
