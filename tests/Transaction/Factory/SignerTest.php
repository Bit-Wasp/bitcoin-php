<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction\Factory;

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Exceptions\SignerException;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputData;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Script\WitnessScript;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Factory\SignData;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\Mutator\TxMutator;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;

class SignerTest extends AbstractTestCase
{

    /**
     * Produces scripts for signing
     * @param EcAdapterInterface $ecAdapter
     * @return array
     */
    public function getScriptVectors(EcAdapterInterface $ecAdapter)
    {
        $privFactory = new PrivateKeyFactory(true, $ecAdapter);
        $results = [];
        foreach ($this->jsonDataFile('signer_fixtures.json')['valid'] as $fixture) {
            $inputs = $fixture['raw']['ins'];
            $outputs = $fixture['raw']['outs'];
            $locktime = isset($fixture['raw']['locktime']) ? $fixture['raw']['locktime'] : 0;
            $policy = Interpreter::VERIFY_NONE | Interpreter::VERIFY_P2SH | Interpreter::VERIFY_WITNESS | Interpreter::VERIFY_CHECKLOCKTIMEVERIFY | Interpreter::VERIFY_CHECKSEQUENCEVERIFY;
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
                $outpoint = new OutPoint($txid, (int) $input['index']);
                $txOut = new TransactionOutput((int) $input['value'], ScriptFactory::fromHex($input['scriptPubKey']));

                $txb->spendOutPoint($outpoint, null, (int) $input['sequence']);
                $keys[] = array_map(function ($array) use ($ecAdapter, $privFactory) {
                    return [$privFactory->fromWif($array['key'], NetworkFactory::bitcoinTestnet()), $array['sigHashType']];
                }, $input['keys']);
                $utxos[] = new Utxo($outpoint, $txOut);

                $signData = new SignData();
                if (array_key_exists('redeemScript', $input) && "" !== $input['redeemScript']) {
                    $signData->p2sh(ScriptFactory::fromHex($input['redeemScript']));
                }
                if (array_key_exists('witnessScript', $input) && "" !== $input['witnessScript']) {
                    $signData->p2wsh(ScriptFactory::fromHex($input['witnessScript']));
                }
                $inputPolicy = isset($input['signaturePolicy']) ? $this->getScriptFlagsFromString($input['signaturePolicy']) : $policy;
                $signData->signaturePolicy($inputPolicy);
                $signDatas[] = $signData;
            }

            $outs = [];
            foreach ($outputs as $output) {
                $out = new TransactionOutput((int) $output['value'], ScriptFactory::fromHex($output['script']));
                $outs[] = $out;
                $txb->output($out->getValue(), $out->getScript());
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
    public function createCredit(ScriptInterface $scriptPubKey, int $value): Utxo
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

            $inputScripts = $inSigner->getInputScripts();
            $this->assertEquals($signData->hasRedeemScript(), $inputScripts->isP2SH());
            $this->assertEquals($signData->hasWitnessScript(), $inputScripts->isP2WSH());

            // in default mode, signScript is always set and always canSign(),
            // implicitly meaning it can never contain a script-hash type
            $signScript = $inputScripts->signScript();
            $this->assertTrue($signScript instanceof OutputData);
            $this->assertTrue($signScript->canSign());
            $this->assertNotEquals(ScriptType::P2SH, $signScript->getType());
            $this->assertNotEquals(ScriptType::P2WSH, $signScript->getType());

            $scriptPubKey = $inputScripts->scriptPubKey();
            // Check some of the script-hash constraints
            if ($signData->hasRedeemScript()) {
                $this->assertEquals(ScriptType::P2SH, $scriptPubKey->getType());
                $redeemScript = $inputScripts->redeemScript();
                // hash in spk matches hash160(rs)
                $this->assertTrue($redeemScript->getScript()->getScriptHash()->equals($scriptPubKey->getSolution()));

                if ($signData->hasWitnessScript()) {
                    $this->assertEquals(ScriptType::P2WSH, $redeemScript->getType());
                    // redeem script solution is the witness script hash
                    $this->assertTrue($inputScripts->witnessScript()->getScript()->getWitnessScriptHash()->equals($redeemScript->getSolution()));
                }
            } else if ($signData->hasWitnessScript()) {
                $this->assertEquals(ScriptType::P2WSH, $scriptPubKey->getType());
                // spk solution is the witness script hash
                $this->assertTrue($inputScripts->witnessScript()->getScript()->getWitnessScriptHash()->equals($scriptPubKey->getSolution()));
            }

            foreach ($signSteps as $keyAndHashType) {
                list ($privateKey, $sigHashType) = $keyAndHashType;
                $signer->sign($i, $privateKey, $utxo->getOutput(), $signData, $sigHashType);
            }

            $this->assertTrue($inSigner->isFullySigned());
            $this->assertEquals(count($signSteps), $inSigner->getRequiredSigs());
            $this->assertTrue($inSigner->verify());
        }

        $signed = $signer->get();
        if (isset($optExtra['whex'])) {
            $this->assertEquals($optExtra['whex'], $signed->getHex());
            $this->assertEquals($optExtra['whex'], $signed->getWitnessSerialization()->getHex());
        } else {
            $this->assertThrows([$signed, 'getWitnessSerialization'], \RuntimeException::class, 'Cannot get witness serialization for transaction without witnesses');
            if (isset($optExtra['hex'])) {
                // If base tx is set, it should be safe to check getHex against that.
                $this->assertEquals($optExtra['hex'], $signed->getHex(), 'transaction matches expected hex');
            }
        }

        if (isset($optExtra['hex'])) {
            $this->assertEquals($optExtra['hex'], $signed->getBaseSerialization()->getHex(), 'transaction matches expected hex');
        }

        $recovered = new Signer($signed, $ecAdapter);
        for ($i = 0, $count = count($utxos); $i < $count; $i++) {
            $origSigner = $signer->input($i, $utxos[$i]->getOutput(), $signDatas[$i]);
            $inSigner = $recovered->input($i, $utxos[$i]->getOutput(), $signDatas[$i]);

            $okey = $origSigner->getPublicKeys();
            $osig = $origSigner->getSignatures();
            $ikey = $inSigner->getPublicKeys();
            $isig = $inSigner->getSignatures();
            
            $this->assertEquals(count($okey), count($ikey), 'should recover same # public keys');
            $this->assertEquals(count($osig), count($isig), 'should recover same # signatures');

            for ($j = 0, $l = count($okey); $j < $l; $j++) {
                if ($okey[$j] === null) {
                    $this->assertEquals(null, $ikey[$j]);
                } else if ($okey[$j] instanceof PublicKeyInterface) {
                    $this->assertInstanceOf(PublicKeyInterface::class, $ikey[$j]);
                    $this->assertTrue($okey[$j]->equals($ikey[$j]));
                } else {
                    throw new \RuntimeException("Strange - getPublicKeys returned a value that was neither null or PublicKeyInterface");
                }
            }

            for ($j = 0, $l = count($osig); $j < $l; $j++) {
                $this->assertTrue($osig[$j]->equals($isig[$j]));
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
     * @param EcAdapterInterface $ecAdapter
     * @return array
     * @throws \Exception
     */
    public function getSimpleSpendCases(EcAdapterInterface $ecAdapter)
    {
        $pubKeyFactory = new PublicKeyFactory($ecAdapter);
        $publicKey = $pubKeyFactory->fromHex('038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b');
        $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, false, $ecAdapter);
        $pubKeyBuffer = $pubKeySerializer->serialize($publicKey);
        $pubKeyHash = Hash::sha256ripe160($pubKeyBuffer);
        return [
            ScriptFactory::sequence([$pubKeyBuffer, Opcodes::OP_CHECKSIG]),
            ScriptFactory::scriptPubKey()->p2pkh($pubKeyHash),
            ScriptFactory::scriptPubKey()->multisigKeyBuffers(1, [$pubKeyBuffer])
        ];
    }

    /**
     * @return array
     */
    public function getSimpleSpendVectors()
    {
        $vectors = [];
        foreach ($this->getEcAdapters() as $adapterFixture) {
            $ecAdapter = $adapterFixture[0];
            foreach ($this->getSimpleSpendCases($ecAdapter) as $script) {
                $vectors[] = [$ecAdapter, $script];
            }
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

        $factory = new PrivateKeyFactory(false, $ecAdapter);
        $privateKey = $factory->fromBuffer(Buffer::int(1, 32));

        $txOut = new TransactionOutput(5000000000, $script);
        $signer = new Signer($tx, $ecAdapter);
        $signer->input(0, $txOut)->sign($privateKey, SigHash::ALL);
    }

    public function testRejectsInvalidSigHashType()
    {
        $outpoint = new OutPoint(new Buffer('', 32), 0xffffffff);
        $txOut = new TransactionOutput(5000000000, ScriptFactory::scriptPubKey()->p2pkh((new Random())->bytes(20)));
        $signer = new Signer((new TxBuilder())
            ->inputs([new TransactionInput($outpoint, new Script())])
            ->outputs([new TransactionOutput(4900000000, new Script)])
            ->get(), Bitcoin::getEcAdapter());

        $input = $signer->input(0, $txOut);
        $this->expectException(SignerException::class);
        $this->expectExceptionMessage("Invalid sigHashType requested");

        $input->getSigHash(20);
    }

    public function testDiscouragesInvalidKeysInScripts()
    {
        $caught = false;

        try {
            $this->doTestSignerInvalidKeyInteraction();
        } catch (\Exception $e) {
            $caught = true;
        }

        $this->assertTrue($caught, "Expect exception to be thrown in default state");
    }

    public function testCanRequireValidKeys()
    {
        $caught = false;
        try {
            $this->doTestSignerInvalidKeyInteraction(false);
        } catch (\Exception $e) {
            $caught = true;
        }

        $this->assertTrue($caught, "Expect exception with invalid key");
    }

    public function testCanDisablePublicKeyValidCheck()
    {
        $caught = false;
        try {
            $this->doTestSignerInvalidKeyInteraction(true);
        } catch (\Exception $e) {
            echo $e->getMessage().PHP_EOL;
            $caught = true;
        }
        $this->assertFalse($caught, "No exception expected when tolerate=true");
    }

    /**
     * @param null|bool $tolerateBadKey
     */
    protected function doTestSignerInvalidKeyInteraction($tolerateBadKey = null)
    {
        $factory = new PrivateKeyFactory(false);
        $myKey = $factory->generate(new Random());
        $badKey = Buffer::hex("031234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd");
        $script = ScriptFactory::scriptPubKey()->multisigKeyBuffers(1, [$myKey->getPublicKey()->getBuffer(), $badKey], false);

        $txOut = new TransactionOutput(123123, $script);

        $dest = new PayToPubKeyHashAddress($myKey->getPubKeyHash());

        $tx = (new TxBuilder())
            ->input("abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234", 0)
            ->payToAddress(121000, $dest)
            ->get();

        $signer = new Signer($tx);
        if (is_bool($tolerateBadKey)) {
            $signer->tolerateInvalidPublicKey($tolerateBadKey);
        }

        $input = $signer->input(0, $txOut);

        if ($tolerateBadKey) {
            // test case has just one invalid key.
            $this->assertInstanceOf(PublicKeyInterface::class, $input->getPublicKeys()[0]);
            $this->assertEquals(null, $input->getPublicKeys()[1]);
        }
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws SignerException
     * @throws \BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidPrivateKey
     * @throws \BitWasp\Bitcoin\Exceptions\UnrecognizedAddressException
     * @throws \Exception
     */
    public function testBitcoinCash(EcAdapterInterface $ecAdapter)
    {
        $expectSpend = '020000000113aaf49280ba92bddfcbdc30d6c7501c2575e4a80f539236df233f9218a2c8400000000049483045022100c5874e39da4dd427d35e24792bf31dcd63c25684deec66b426271b4043e21c3002201bfdc0621ad4237e8db05aa6cad69f3d5ab4ae32ebb2048f65b12165da6cc69341ffffffff0100f2052a010000001976a914cd29cc97826c37281ac61301e4d5ed374770585688ac00000000';
        $value = 50 * 100000000;

        $txid = "40c8a218923f23df3692530fa8e475251c50c7d630dccbdfbd92ba8092f4aa13";
        $vout = 0;
        $network = NetworkFactory::bitcoinTestnet();

        $wif = "cTNwkxh7nVByhc3i7BH6eaBFQ4yVs6WvXBGBoA9xdKiorwcYVACc";
        $prvFactory = new PrivateKeyFactory(true, $ecAdapter);
        $keyPair = $prvFactory->fromWif($wif, $network);

        $spk = ScriptFactory::scriptPubKey()->payToPubKey($keyPair->getPublicKey());
        $addrCreator = new AddressCreator();
        $dest = $addrCreator->fromString('mzDktdwPcWwqg8aZkPotx6aYi4mKvDD7ay', $network)->getScriptPubKey();

        $txb = (new TxBuilder())
            ->version(2)
            ->input($txid, $vout)
            ->output($value, $dest);

        $txs = new Signer($txb->get(), $ecAdapter);
        $txs->redeemBitcoinCash();

        $hashType = SigHash::BITCOINCASH | SigHash::ALL;

        $input = $txs->input(0, new TransactionOutput($value, $spk));
        $input->sign($keyPair, $hashType);

        $tx = $txs->get();
        $this->assertEquals($expectSpend, $tx->getHex());
    }

    public function testDontSignInput()
    {
        $expectSpend = '020000000113aaf49280ba92bddfcbdc30d6c7501c2575e4a80f539236df233f9218a2c8400000000000ffffffff0100f2052a010000001976a914cd29cc97826c37281ac61301e4d5ed374770585688ac00000000';
        $value = 50 * 100000000;

        $txid = "40c8a218923f23df3692530fa8e475251c50c7d630dccbdfbd92ba8092f4aa13";
        $vout = 0;
        $network = NetworkFactory::bitcoinTestnet();

        $addrCreator = new AddressCreator();
        $dest = $addrCreator->fromString('mzDktdwPcWwqg8aZkPotx6aYi4mKvDD7ay', $network)->getScriptPubKey();

        $txb = (new TxBuilder())
            ->version(2)
            ->input($txid, $vout)
            ->output($value, $dest);

        $txs = new Signer($txb->get());

        $tx = $txs->get();
        $this->assertEquals($expectSpend, $tx->getHex());
    }

    public function paddedMultisigsProvider()
    {
        $privFactory = new PrivateKeyFactory(true);
        $keys = [
            $privFactory->fromWif('KzzM4K74i3uoUKHZqfBRR44T1zcChzZFMjZkxZZReiTkSPkFv6jY'),
            $privFactory->fromWif('L34yCtA8pZ2pGjdg2sKYJ5BwqW1rQYVNdSPMbRuCUfpJN9XkMqVR'),
            $privFactory->fromWif('KxuuCULSevY215pnSPtRHka3YH83D9w2MKtwg1y33L6pBzk3tjQ1'),
        ];

        $multisig = ScriptFactory::scriptPubKey()->multisig(2, array_map(function (PrivateKeyInterface $key) {
            return $key->getPublicKey();
        }, $keys), false);

        $p2sh = new P2shScript($multisig);
        $p2wsh = new WitnessScript($multisig);

        $value = 40000;

        $p2shTxout = new TransactionOutput($value, $p2sh->getOutputScript());
        $p2shSignData = (new SignData())
            ->p2sh($p2sh);
        $p2wshTxout = new TransactionOutput($value, $p2wsh->getOutputScript());
        $p2wshSignData = (new SignData())
            ->p2wsh($p2wsh);

        $addrCreator = new AddressCreator();
        $unsigned = (new TxBuilder())
            ->input('5077666f78045cb3482f64ee5d203366363af71436c1bb6db8b49c428a53f00d', 0)
            ->payToAddress($value, $addrCreator->fromString('3EppTrJXEgNHgHoSRQdQaVQV4VS7Tg7aSs'))
            ->get();

        $experimentsPart = [
            [0],
            [1],
            [2],
        ];

        $experimentsFull = [
            [0, 1],
            [1, 2],
            [0, 2],
        ];

        $mkFixture = function (array $experiment, TransactionOutputInterface $txOut, SignData $signData) use ($keys, $unsigned) {
            $use = [];
            foreach ($experiment as $idx) {
                $use[] = $keys[$idx];
            }

            return [$use, $unsigned, $txOut, $signData];
        };

        $mkP2sh = function (array $experiment) use ($p2shTxout, $p2shSignData, $mkFixture) {
            return $mkFixture($experiment, $p2shTxout, $p2shSignData);
        };
        $mkP2wsh = function (array $experiment) use ($p2wshTxout, $p2wshSignData, $mkFixture) {
            return $mkFixture($experiment, $p2wshTxout, $p2wshSignData);
        };

        return array_merge(
            array_map($mkP2sh, $experimentsPart),
            array_map($mkP2wsh, $experimentsPart),
            array_map($mkP2sh, $experimentsFull),
            array_map($mkP2wsh, $experimentsFull)
        );
    }

    /**
     * @param array $privKeys
     * @param TransactionInterface $tx
     * @param TransactionOutputInterface $txOut
     * @param SignData $signData
     * @dataProvider paddedMultisigsProvider
     */
    public function testPaddingMultisig(array $privKeys, TransactionInterface $tx, TransactionOutputInterface $txOut, SignData $signData)
    {
        $signer = (new Signer($tx))
            ->padUnsignedMultisigs(true)
        ;

        $input = $signer->input(0, $txOut, $signData);

        $lastCount = count($input->getSignatures());
        foreach ($privKeys as $key) {
            $input->sign($key, SigHash::ALL);

            // Check it signed, for the sake of the test
            $this->assertEquals($lastCount + 1, count($input->getSignatures()));
            $lastCount++;
        }

        $signed = $signer->get();

        $signerAgain = (new Signer($signed))->padUnsignedMultisigs(true);
        $inputAgain = $signerAgain->input(0, $txOut, $signData);

        for ($i = 0; $i < $input->getRequiredSigs(); $i++) {
            if (array_key_exists($i, $input->getSignatures())) {
                $this->assertTrue(array_key_exists($i, $inputAgain->getSignatures()), "Missing or misplaced signature");

                $a = $input->getSignatures()[$i];
                $b = $input->getSignatures()[$i];
                if ($a instanceof TransactionSignatureInterface) {
                    $this->assertInstanceOf(TransactionSignatureInterface::class, $b);
                    $this->assertTrue($a->equals($b));
                }
            }
        }

        $this->assertEquals($input->isFullySigned(), $inputAgain->isFullySigned());
    }

    public function testFullySignedMultisigIsNotPadded()
    {
        $privFactory = new PrivateKeyFactory(true);
        $keys = [
            $privFactory->fromWif('KzzM4K74i3uoUKHZqfBRR44T1zcChzZFMjZkxZZReiTkSPkFv6jY'),
            $privFactory->fromWif('L34yCtA8pZ2pGjdg2sKYJ5BwqW1rQYVNdSPMbRuCUfpJN9XkMqVR'),
            $privFactory->fromWif('KxuuCULSevY215pnSPtRHka3YH83D9w2MKtwg1y33L6pBzk3tjQ1'),
        ];

        $multisig = ScriptFactory::scriptPubKey()->multisig(2, array_map(function (PrivateKeyInterface $key) {
            return $key->getPublicKey();
        }, $keys), false);

        $p2sh = new P2shScript($multisig);
        $addr = $p2sh->getAddress();

        $value = 40000;
        $txOut = new TransactionOutput($value, $addr->getScriptPubKey());

        $addrCreator = new AddressCreator();
        $unsigned = (new TxBuilder())
            ->input('5077666f78045cb3482f64ee5d203366363af71436c1bb6db8b49c428a53f00d', 0)
            ->payToAddress($value, $addrCreator->fromString('3EppTrJXEgNHgHoSRQdQaVQV4VS7Tg7aSs'))
            ->get();

        $signData = (new SignData())
            ->p2sh($p2sh);

        $signer = (new Signer($unsigned))
            ->padUnsignedMultisigs(true)
        ;

        $signer
            ->input(0, $txOut, $signData)
            ->sign($keys[0], SigHash::ALL)
            ->sign($keys[1], SigHash::ALL)
        ;

        $signed = $signer->get();

        $fullySigned = $signed->getInput(0)->getScript();
        $chunks = $fullySigned->getScriptParser()->decode();
        $op_0 = new Operation(Opcodes::OP_0, new Buffer());

        for ($i = 0; $i < count($chunks); ++$i) {
            $copy =[];
            foreach ($chunks as $j => $c) {
                if ($i == $j) {
                    $copy[] = $op_0;
                }
                $copy[] = $c;
            }

            $txMut = new TxMutator($signed);
            $txMut->inputsMutator()[0]->script(ScriptFactory::fromOperations($copy));
            $txInvalid = $txMut->done();

            $exception = null;
            try {
                $signerAgain = (new Signer($txInvalid))
                    ->padUnsignedMultisigs(true)
                ;

                $signerAgain->input(0, $txOut, $signData);
            } catch (\Exception $e) {
                $exception = $e;
            }

            $this->assertInstanceOf(SignerException::class, $exception);
            $this->assertEquals("Padding is forbidden for a fully signed multisig script", $exception->getMessage());
        }
    }

    public function testFullySignedWitnessMultisigIsNotPadded()
    {
        $privFactory = new PrivateKeyFactory(true);
        $keys = [
            $privFactory->fromWif('KzzM4K74i3uoUKHZqfBRR44T1zcChzZFMjZkxZZReiTkSPkFv6jY'),
            $privFactory->fromWif('L34yCtA8pZ2pGjdg2sKYJ5BwqW1rQYVNdSPMbRuCUfpJN9XkMqVR'),
            $privFactory->fromWif('KxuuCULSevY215pnSPtRHka3YH83D9w2MKtwg1y33L6pBzk3tjQ1'),
        ];

        $multisig = ScriptFactory::scriptPubKey()->multisig(2, array_map(function (PrivateKeyInterface $key) {
            return $key->getPublicKey();
        }, $keys), false);

        $p2wsh = new WitnessScript($multisig);

        $value = 40000;
        $txOut = new TransactionOutput($value, $p2wsh->getOutputScript());

        $addrCreator = new AddressCreator();
        $unsigned = (new TxBuilder())
            ->input('5077666f78045cb3482f64ee5d203366363af71436c1bb6db8b49c428a53f00d', 0)
            ->payToAddress($value, $addrCreator->fromString('3EppTrJXEgNHgHoSRQdQaVQV4VS7Tg7aSs'))
            ->get();

        $signData = (new SignData())
            ->p2wsh($p2wsh);

        $signer = (new Signer($unsigned))
            ->padUnsignedMultisigs(true)
        ;

        $signer
            ->input(0, $txOut, $signData)
            ->sign($keys[0], SigHash::ALL)
            ->sign($keys[1], SigHash::ALL)
        ;

        $signed = $signer->get();

        $chunks = $signed->getWitness(0)->all();
        $op_0 = new Buffer();

        for ($i = 0; $i < count($chunks); ++$i) {
            $copy =[];
            foreach ($chunks as $j => $c) {
                if ($i == $j) {
                    $copy[] = $op_0;
                }
                $copy[] = $c;
            }

            $txMut = new TxMutator($signed);
            $txMut->witness([
                new ScriptWitness(...$copy)
            ]);
            $txInvalid = $txMut->done();

            $exception = null;
            try {
                $signerAgain = (new Signer($txInvalid))
                    ->padUnsignedMultisigs(true)
                ;

                $signerAgain->input(0, $txOut, $signData);
            } catch (\Exception $e) {
                $exception = $e;
            }

            $this->assertInstanceOf(SignerException::class, $exception);
            $this->assertEquals("Padding is forbidden for a fully signed multisig script", $exception->getMessage());
        }
    }
}
