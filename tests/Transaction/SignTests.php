<?php

namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Factory\SignData;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;

class SignTests extends AbstractTestCase
{

    public function getSupportedSignTypes()
    {
        return [
            OutputClassifier::PAYTOPUBKEYHASH,
            OutputClassifier::PAYTOPUBKEY,
            OutputClassifier::MULTISIG
        ];
    }

    public function getScriptAndKeys($type, EcAdapterInterface $ecAdapter)
    {
        if ($type === OutputClassifier::WITNESS_V0_KEYHASH) {
            $privateKey = PrivateKeyFactory::create(true, $ecAdapter);
            $script = ScriptFactory::scriptPubKey()->witnessKeyHash($privateKey->getPublicKey()->getPubKeyHash());
            return [$script, [$privateKey]];
        } else if ($type === OutputClassifier::PAYTOPUBKEY) {
            $privateKey = PrivateKeyFactory::create(true, $ecAdapter);
            $script = ScriptFactory::scriptPubKey()->payToPubKey($privateKey->getPublicKey());
            return [$script, [$privateKey]];
        } else if ($type === OutputClassifier::PAYTOPUBKEYHASH) {
            $privateKey = PrivateKeyFactory::create(true, $ecAdapter);
            $script = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPubKeyHash());
            return [$script, [$privateKey]];
        } else if ($type === OutputClassifier::MULTISIG) {
            $privateKey1 = PrivateKeyFactory::create(true, $ecAdapter);
            $privateKey2 = PrivateKeyFactory::create(true, $ecAdapter);
            $script = ScriptFactory::scriptPubKey()->multisig(2, [$privateKey1->getPublicKey(), $privateKey2->getPublicKey()]);
            return [$script, [$privateKey1, $privateKey2]];
        } else {
            throw new \RuntimeException('Unexpected scriptPubKey type requested for vector');
        }
    }

    private function p2shScript(ScriptInterface $p2shScript)
    {
        $scriptPubKey = ScriptFactory::scriptPubKey()->payToScriptHash(Hash::sha256ripe160($p2shScript->getBuffer()));
        return [$scriptPubKey, $p2shScript, null];
    }

    private function p2wshScript(ScriptInterface $witnessScript)
    {
        $scriptPubKey = ScriptFactory::scriptPubKey()->witnessScriptHash(Hash::sha256($witnessScript->getBuffer()));
        return [$scriptPubKey, null, $witnessScript];
    }
    private function p2shp2wshScript(ScriptInterface $witnessScript)
    {
        $p2shScript = ScriptFactory::scriptPubKey()->witnessScriptHash(Hash::sha256($witnessScript->getBuffer()));
        $scriptPubKey = ScriptFactory::scriptPubKey()->payToScriptHash(Hash::sha256ripe160($p2shScript->getBuffer()));

        return [$scriptPubKey, $p2shScript, $witnessScript];
    }

    private function p2wpkhScripts(EcAdapterInterface $ecAdapter)
    {
        list ($p2wpkh, $keys)  = $this->getScriptAndKeys(OutputClassifier::WITNESS_V0_KEYHASH, $ecAdapter);
        return [
            [$ecAdapter, $keys, $p2wpkh, null, null],
            array_merge([$ecAdapter, $keys], $this->p2shScript($p2wpkh))
        ];
    }

    public function getScriptVectors(EcAdapterInterface $ecAdapter)
    {
        $results = [];
        foreach ($this->getSupportedSignTypes() as $type) {
            list ($script, $keys) = $this->getScriptAndKeys($type, $ecAdapter);
            $start = [$ecAdapter, $keys];
            $results[] = array_merge($start, [$script, null, null]);
            $results[] = array_merge($start, $this->p2shScript($script));
            $results[] = array_merge($start, $this->p2wshScript($script));
            $results[] = array_merge($start, $this->p2shp2wshScript($script));
        }

        $results = array_merge($results, $this->p2wpkhScripts($ecAdapter));
        return $results;
    }

    public function getVectors()
    {
        $results = [];
        foreach ($this->getEcAdapters() as $ecAdapter) {
            $results = array_merge($results, $this->getScriptVectors($ecAdapter[0]));
        }
        print_r($results);
        return $results;
    }

    public function createCredit(ScriptInterface $scriptPubKey, $value)
    {
        return new Utxo(new OutPoint(new Buffer(random_bytes(32)), 0), new TransactionOutput($value, $scriptPubKey));
    }

    /**
     * @dataProvider getVectors
     * @param EcAdapterInterface $ecAdapter
     * @param array $privateKeys
     * @param ScriptInterface $scriptPubKey
     * @param ScriptInterface|null $redeemScript
     * @param ScriptInterface|null $witnessScript
     * @param int $sigHashType
     */
    public function testCases(EcAdapterInterface $ecAdapter, array $privateKeys, ScriptInterface $scriptPubKey, ScriptInterface $redeemScript = null, ScriptInterface $witnessScript = null, $sigHashType = SigHash::ALL)
    {
        $amount = 100000;
        $flags = Interpreter::VERIFY_DERSIG | Interpreter::VERIFY_WITNESS | Interpreter::VERIFY_P2SH | Interpreter::VERIFY_CLEAN_STACK;

        $utxo = $this->createCredit($scriptPubKey, $amount);
        $txOut = $utxo->getOutput();

        // Create signed transaction
        $unsigned = (new TxBuilder)
            ->spendOutPoint($utxo->getOutPoint())
            ->output($txOut->getValue() - 6000, $txOut->getScript())
            ->get();
        echo $txOut->getScript()->getScriptParser()->getHumanReadable().PHP_EOL;
        $signData = new SignData();
        if ($redeemScript) {
            $signData->p2sh($redeemScript);
        }
        if ($witnessScript) {
            $signData->p2wsh($witnessScript);
        }
        if ($flags) {
            $signData->signaturePolicy($flags);
        }

        $signer = new Signer($unsigned, $ecAdapter);
        foreach ($privateKeys as $key) {
            $signer->sign(0, $key, $txOut, $signData, $sigHashType);
        }

        $spendTx = $signer->get();
        $inSigner = $signer->signer(0, $txOut, $signData);
        $this->assertTrue($inSigner->isFullySigned());
        $this->assertEquals($inSigner->getRequiredSigs(), count($privateKeys), '# sigs should match # keys');

        $this->checkWeCanVerifySignatures($spendTx, $utxo->getOutput(), $flags);
        $this->checkWeCanRecoverState($spendTx, $privateKeys, $utxo, $signData);
    }

    /**
     * @param TransactionInterface $spendTx
     * @param TransactionOutputInterface $txOut
     * @param $flags
     */
    public function checkWeCanVerifySignatures(TransactionInterface $spendTx, TransactionOutputInterface $txOut, $flags)
    {
        $this->assertTrue($spendTx->validator()->checkSignature(ScriptFactory::consensus(), $flags, 0, $txOut));
    }

    /**
     * @param TransactionInterface $spendTx
     * @param array $keys
     * @param Utxo $utxo
     * @param SignData $signData
     */
    public function checkWeCanRecoverState(TransactionInterface $spendTx, array $keys, Utxo $utxo, SignData $signData)
    {
        $recovered = new Signer($spendTx);
        $inSigner = $recovered->signer(0, $utxo->getOutput(), $signData);

        $this->assertTrue($inSigner->isFullySigned());
        $this->assertEquals($inSigner->getRequiredSigs(), count($keys), '# sigs should match # keys');
    }
}
