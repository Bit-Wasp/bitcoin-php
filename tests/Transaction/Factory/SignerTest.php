<?php

namespace BitWasp\Bitcoin\Tests\Transaction\Factory;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Factory\InputSigner;
use BitWasp\Bitcoin\Transaction\Factory\SignData;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;

class SignerTest extends AbstractTestCase
{

    /**
     * @return string[]
     */
    public function getSupportedSignTypes()
    {
        return [
            OutputClassifier::PAYTOPUBKEYHASH,
            OutputClassifier::PAYTOPUBKEY,
            OutputClassifier::MULTISIG
        ];
    }

    /**
     * @param string $type
     * @param EcAdapterInterface $ecAdapter
     * @return array
     */
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

    /**
     * Convenience function to create P2SH version of given script
     * @param ScriptInterface $p2shScript
     * @return array
     */
    private function p2shScript(ScriptInterface $p2shScript)
    {
        $scriptPubKey = ScriptFactory::scriptPubKey()->payToScriptHash(Hash::sha256ripe160($p2shScript->getBuffer()));
        return [$scriptPubKey, $p2shScript, null];
    }

    /**
     * Convenience function to create P2WSH version of given script
     * @param ScriptInterface $witnessScript
     * @return array
     */
    private function p2wshScript(ScriptInterface $witnessScript)
    {
        $scriptPubKey = ScriptFactory::scriptPubKey()->witnessScriptHash(Hash::sha256($witnessScript->getBuffer()));
        return [$scriptPubKey, null, $witnessScript];
    }

    /**
     * Convenience function to create P2WSH version of given script
     * @param ScriptInterface $witnessScript
     * @return array
     */
    private function p2shp2wshScript(ScriptInterface $witnessScript)
    {
        $p2shScript = ScriptFactory::scriptPubKey()->witnessScriptHash(Hash::sha256($witnessScript->getBuffer()));
        $scriptPubKey = ScriptFactory::scriptPubKey()->payToScriptHash(Hash::sha256ripe160($p2shScript->getBuffer()));

        return [$scriptPubKey, $p2shScript, $witnessScript];
    }

    /**
     * Produces test vectors for allowed P2WPKH representations
     *
     * @param EcAdapterInterface $ecAdapter
     * @return array
     */
    private function p2wpkhTests(EcAdapterInterface $ecAdapter)
    {
        list ($p2wpkh, $keys)  = $this->getScriptAndKeys(OutputClassifier::WITNESS_V0_KEYHASH, $ecAdapter);
        return [
            [$ecAdapter, $keys, $p2wpkh, null, null],
            array_merge([$ecAdapter, $keys], $this->p2shScript($p2wpkh))
        ];
    }

    /**
     * Produces scripts for signing
     * @param EcAdapterInterface $ecAdapter
     * @return array
     */
    public function getScriptVectors(EcAdapterInterface $ecAdapter)
    {
        $results = [];
        foreach ($this->jsonDataFile('signer_fixtures.json')['valid'] as $fixture) {
            $inputs = $fixture['raw']['ins'];
            $outputs = $fixture['raw']['outs'];
            $locktime = isset($fixture['raw']['locktime']) ? $fixture['raw']['locktime'] : 0;
            $policy = Interpreter::VERIFY_NONE | Interpreter::VERIFY_DERSIG | Interpreter::VERIFY_MINIMALDATA;
            if (isset($fixture['signaturePolicy'])) {
                $policy = $this->getScriptFlagsFromString($fixture['signaturePolicy']);
            }

            $utxos = [];
            $keys = [];
            $signDatas = [];
            $txb = new TxBuilder();
            $txb->locktime($locktime);
            foreach ($inputs as $input) {
                $hash = Buffer::hex($input['hash']);
                $txid = $hash->flip();
                $outpoint = new OutPoint($txid, $input['index']);
                $txOut = new TransactionOutput($input['value'], ScriptFactory::fromHex($input['scriptPubKey']));

                $txb->spendOutPoint($outpoint, null, $input['sequence']);
                $keys[] = array_map(function ($array) use ($ecAdapter) {
                    return [PrivateKeyFactory::fromWif($array['key'], $ecAdapter, NetworkFactory::bitcoinTestnet()), $array['sigHashType']];
                }, $input['keys']);
                $utxos[] = new Utxo($outpoint, $txOut);

                $signData = new SignData();
                $signData->p2sh(ScriptFactory::fromHex($input['redeemScript']));
                $signData->p2wsh(ScriptFactory::fromHex($input['witnessScript']));
                $signData->signaturePolicy(isset($input['signaturePolicy']) ? $this->getScriptFlagsFromString($input['signaturePolicy']) : $policy);
                $signDatas[] = $signData;
            }

            $outs = [];
            foreach ($outputs as $output) {
                $out = new TransactionOutput($output['value'], ScriptFactory::fromHex($output['script']));
                $outs[] = $out;
                $txb->output($output['value'], ScriptFactory::fromHex($output['script']));
            }

            $optExtra = [];
            if (isset($fixture['hex']) && $fixture['hex'] !== '') {
                $optExtra['hex'] = $fixture['hex'];
            }
            if (isset($fixture['whex']) && $fixture['whex'] !== '') {
                $optExtra['whex'] = $fixture['whex'];
            }
            $results[] = [$fixture['description'], $ecAdapter, $txb, $utxos, $signDatas, $keys, $optExtra];
        }

        return $results;
    }

    /**
     * Produces ALL vectors
     * @return array
     */
    public function getVectors()
    {
        $results = [];
        foreach ($this->getEcAdapters() as $ecAdapter) {
            $results = array_merge($results, $this->getScriptVectors($ecAdapter[0]));
        }
        return $results;
    }

    /**
     * Create a mock UTXO to spend
     * @param ScriptInterface $scriptPubKey
     * @param int $value
     * @return Utxo
     */
    public function createCredit(ScriptInterface $scriptPubKey, $value)
    {
        return new Utxo(new OutPoint(new Buffer(random_bytes(32)), 0), new TransactionOutput($value, $scriptPubKey));
    }

    /**
     * @param string $description
     * @param EcAdapterInterface $ecAdapter
     * @param Utxo[] $utxos
     * @param TxBuilder $builder
     * @param SignData[] $signDatas
     * @param array $keys
     * @param array $optExtra
     * @dataProvider getVectors
     */
    public function testCases($description, EcAdapterInterface $ecAdapter, TxBuilder $builder, array $utxos, array $signDatas, array $keys, array $optExtra)
    {

        $signer = new Signer($builder->get(), $ecAdapter);
        for ($i = 0, $count = count($utxos); $i < $count; $i++) {
            $utxo = $utxos[$i];
            $signData = $signDatas[$i];
            $signSteps = $keys[$i];
            $inSigner = $signer->input($i, $utxo->getOutput(), $signData);
            $this->assertFalse($inSigner->isFullySigned());
            $this->assertFalse($inSigner->verify());

            foreach ($signSteps as $keyAndHashType) {
                list ($privateKey, $sigHashType) = $keyAndHashType;
                $signer->sign($i, $privateKey, $utxo->getOutput(), $signData, $sigHashType);
            }

            $this->assertTrue($inSigner->isFullySigned());
            $this->assertEquals(count($signSteps), $inSigner->getRequiredSigs());
            $this->assertTrue($inSigner->verify());
        }

        $signed = $signer->get();
        if (isset($optExtra['hex'])) {
            $this->assertEquals($optExtra['hex'], $signed->getHex(), 'transaction matches expected hex');
        }
        if (isset($optExtra['whex'])) {
            $this->assertEquals($optExtra['whex'], $signed->getWitnessBuffer()->getHex());
        }

        $recovered = new Signer($signed);
        for ($i = 0, $count = count($utxos); $i < $count; $i++) {
            $origSigner = $signer->input($i, $utxos[$i]->getOutput(), $signDatas[$i]);
            $inSigner = $recovered->input($i, $utxos[$i]->getOutput(), $signDatas[$i]);
            $this->assertEquals(count($origSigner->getPublicKeys()), count($inSigner->getPublicKeys()), 'should recover same # public keys');
            $this->assertEquals(count($origSigner->getSignatures()), count($inSigner->getSignatures()), 'should recover same # signatures');

            for ($j = 0, $l = count($origSigner->getPublicKeys()); $j < $l; $j++) {
                $this->assertEquals($origSigner->getPublicKeys()[$j]->getBinary(), $inSigner->getPublicKeys()[$j]->getBinary());
            }

            for ($j = 0, $l = count($origSigner->getSignatures()); $j < $l; $j++) {
                $this->assertEquals($origSigner->getSignatures()[$j]->getBinary(), $inSigner->getSignatures()[$j]->getBinary());
            }

            $this->assertEquals($origSigner->isFullySigned(), $inSigner->isFullySigned(), 'should recover same isFullySigned');
            $this->assertEquals($origSigner->getRequiredSigs(), $inSigner->getRequiredSigs(), 'should recover same # requiredSigs');

            $origValues = $origSigner->serializeSignatures();
            $inValues = $inSigner->serializeSignatures();
            $this->assertTrue($origValues->getScriptSig()->equals($inValues->getScriptSig()));
            $this->assertTrue($origValues->getScriptWitness()->equals($inValues->getScriptWitness()));
            $this->assertTrue($inSigner->verify());
        }
    }

    /**
     * @param TransactionInterface $spendTx
     * @param Utxo $utxo
     * @param SignData $signData
     * @param InputSigner $origSigner
     */
    public function checkWeCanRecoverState(TransactionInterface $spendTx, Utxo $utxo, SignData $signData, InputSigner $origSigner)
    {
        $recovered = new Signer($spendTx);
        $inSigner = $recovered->input(0, $utxo->getOutput(), $signData);

        $this->assertEquals(count($origSigner->getPublicKeys()), count($inSigner->getPublicKeys()), 'should recover same # public keys');
        $this->assertEquals(count($origSigner->getSignatures()), count($inSigner->getSignatures()), 'should recover same # signatures');

        for ($i = 0, $l = count($origSigner->getPublicKeys()); $i < $l; $i++) {
            $this->assertEquals($origSigner->getPublicKeys()[$i]->getBinary(), $inSigner->getPublicKeys()[$i]->getBinary());
        }

        for ($i = 0, $l = count($origSigner->getSignatures()); $i < $l; $i++) {
            $this->assertEquals($origSigner->getSignatures()[$i]->getBinary(), $inSigner->getSignatures()[$i]->getBinary());
        }

        $this->assertEquals($origSigner->isFullySigned(), count($inSigner->isFullySigned()), 'should recover same isFullySigned');
        $this->assertEquals($origSigner->getRequiredSigs(), $inSigner->getRequiredSigs(), 'should recover same # requiredSigs');

        $origValues = $origSigner->serializeSignatures();
        $inValues = $inSigner->serializeSignatures();
        $this->assertTrue($origValues->getScriptSig()->equals($inValues->getScriptSig()));
        $this->assertTrue($origValues->getScriptWitness()->equals($inValues->getScriptWitness()));
    }

    /**
     * @return ScriptInterface[]
     */
    public function getSimpleSpendCases()
    {
        $publicKey = PublicKeyFactory::fromHex('038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b');
        return [
            ScriptFactory::scriptPubKey()->p2pk($publicKey),
            ScriptFactory::scriptPubKey()->p2pkh($publicKey->getPubKeyHash()),
            ScriptFactory::scriptPubKey()->multisig(1, [$publicKey])
        ];
    }

    /**
     * @return array
     */
    public function getSimpleSpendVectors()
    {
        $ecAdapter = Bitcoin::getEcAdapter();
        $vectors = [];
        foreach ($this->getSimpleSpendCases() as $script) {
            $vectors[] = [$ecAdapter, $script];
        }
        return $vectors;
    }

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param ScriptInterface $script
     * @dataProvider getSimpleSpendVectors
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Signing with the wrong private key
     */
    public function testRejectsWrongKey(EcAdapterInterface $ecAdapter, ScriptInterface $script)
    {
        $outpoint = new OutPoint(new Buffer('', 32), 0xffffffff);

        $tx = (new TxBuilder())
            ->inputs([new TransactionInput($outpoint, new Script())])
            ->outputs([new TransactionOutput(4900000000, $script)])
            ->get();

        $privateKey = PrivateKeyFactory::fromInt(1);
        $txOut = new TransactionOutput(5000000000, $script);
        $signer = new Signer($tx, $ecAdapter);
        $signer->input(0, $txOut)->sign($privateKey, SigHash::ALL);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid sigHashType requested
     */
    public function testRejectsInvalidSigHashType()
    {
        $outpoint = new OutPoint(new Buffer('', 32), 0xffffffff);
        $txOut = new TransactionOutput(5000000000, ScriptFactory::scriptPubKey()->p2pkh((new Random())->bytes(20)));
        $signer = new Signer((new TxBuilder())
            ->inputs([new TransactionInput($outpoint, new Script())])
            ->outputs([new TransactionOutput(4900000000, new Script)])
            ->get(), Bitcoin::getEcAdapter());

        $signer->input(0, $txOut)->getSigHash(20);
    }

}
