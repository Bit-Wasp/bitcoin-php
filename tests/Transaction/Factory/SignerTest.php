<?php

namespace BitWasp\Bitcoin\Tests\Transaction\Factory;

use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
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
                $outpoint = new OutPoint($txid, $input['index']);
                $txOut = new TransactionOutput($input['value'], ScriptFactory::fromHex($input['scriptPubKey']));

                $txb->spendOutPoint($outpoint, null, $input['sequence']);
                $keys[] = array_map(function ($array) use ($ecAdapter) {
                    return [PrivateKeyFactory::fromWif($array['key'], $ecAdapter, NetworkFactory::bitcoinTestnet()), $array['sigHashType']];
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

            $this->assertEquals($signData->hasRedeemScript(), $inSigner->isP2SH());
            $this->assertEquals($signData->hasWitnessScript(), $inSigner->isP2WSH());

            // in default mode, signScript is always set and always canSign(),
            // implicitly meaning it can never contain a script-hash type
            $this->assertTrue($inSigner->getSignScript() instanceof OutputData);
            $this->assertTrue($inSigner->getSignScript()->canSign());
            $this->assertNotEquals(ScriptType::P2SH, $inSigner->getSignScript());
            $this->assertNotEquals(ScriptType::P2WSH, $inSigner->getSignScript());

            // Check some of the script-hash constraints
            if ($signData->hasRedeemScript()) {
                $this->assertEquals(ScriptType::P2SH, $inSigner->getScriptPubKey()->getType());
                // hash in spk matches hash160(rs)
                $this->assertTrue($inSigner->getRedeemScript()->getScript()->getScriptHash()->equals($inSigner->getScriptPubKey()->getSolution()));

                if ($signData->hasWitnessScript()) {
                    $this->assertEquals(ScriptType::P2WSH, $inSigner->getRedeemScript()->getType());
                    // redeem script solution is the witness script hash
                    $this->assertTrue($inSigner->getWitnessScript()->getScript()->getWitnessScriptHash()->equals($inSigner->getRedeemScript()->getSolution()));
                }
            } else if ($signData->hasWitnessScript()) {
                $this->assertEquals(ScriptType::P2WSH, $inSigner->getScriptPubKey()->getType());
                // spk solution is the witness script hash
                $this->assertTrue($inSigner->getWitnessScript()->getScript()->getWitnessScriptHash()->equals($inSigner->getScriptPubKey()->getSolution()));
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

        $recovered = new Signer($signed);
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
     * @param null $tolerateBadKey
     */
    protected function doTestSignerInvalidKeyInteraction($tolerateBadKey = null)
    {
        $myKey = PrivateKeyFactory::create();
        $badKey = Buffer::hex("031234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd");
        $script = ScriptFactory::scriptPubKey()->multisigKeyBuffers(1, [$myKey->getPublicKey()->getBuffer(), $badKey], false);

        $txOut = new TransactionOutput(123123, $script);

        $dest = $myKey->getPublicKey()->getAddress();

        $tx = (new TxBuilder())
            ->input("abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234", 0)
            ->payToAddress(121000, $dest)
            ->get();

        $signer = new Signer($tx);
        if ($tolerateBadKey != null) {
            $signer->tolerateInvalidPublicKey($tolerateBadKey);
        }

        $input = $signer->input(0, $txOut);

        if ($tolerateBadKey) {
            // test case has just one invalid key.
            $this->assertInstanceOf(PublicKeyInterface::class, $input->getPublicKeys()[0]);
            $this->assertEquals(null, $input->getPublicKeys()[1]);
        }
    }

    public function testBitcoinCash()
    {
        $expectSpend = '020000000113aaf49280ba92bddfcbdc30d6c7501c2575e4a80f539236df233f9218a2c8400000000049483045022100c5874e39da4dd427d35e24792bf31dcd63c25684deec66b426271b4043e21c3002201bfdc0621ad4237e8db05aa6cad69f3d5ab4ae32ebb2048f65b12165da6cc69341ffffffff0100f2052a010000001976a914cd29cc97826c37281ac61301e4d5ed374770585688ac00000000';
        $value = 50 * 100000000;

        $txid = "40c8a218923f23df3692530fa8e475251c50c7d630dccbdfbd92ba8092f4aa13";
        $vout = 0;
        $network = NetworkFactory::bitcoinTestnet();

        $wif = "cTNwkxh7nVByhc3i7BH6eaBFQ4yVs6WvXBGBoA9xdKiorwcYVACc";
        $keyPair = PrivateKeyFactory::fromWif($wif, null, $network);

        $spk = ScriptFactory::scriptPubKey()->payToPubKey($keyPair->getPublicKey());
        $dest = AddressFactory::fromString('mzDktdwPcWwqg8aZkPotx6aYi4mKvDD7ay', $network)->getScriptPubKey();

        $txb = (new TxBuilder())
            ->version(2)
            ->input($txid, $vout)
            ->output($value, $dest);

        $txs = new Signer($txb->get());
        $txs->redeemBitcoinCash(true);

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

        $dest = AddressFactory::fromString('mzDktdwPcWwqg8aZkPotx6aYi4mKvDD7ay', $network)->getScriptPubKey();

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
        $keys = [
            PrivateKeyFactory::fromWif('KzzM4K74i3uoUKHZqfBRR44T1zcChzZFMjZkxZZReiTkSPkFv6jY'),
            PrivateKeyFactory::fromWif('L34yCtA8pZ2pGjdg2sKYJ5BwqW1rQYVNdSPMbRuCUfpJN9XkMqVR'),
            PrivateKeyFactory::fromWif('KxuuCULSevY215pnSPtRHka3YH83D9w2MKtwg1y33L6pBzk3tjQ1'),
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

        $unsigned = (new TxBuilder())
            ->input('5077666f78045cb3482f64ee5d203366363af71436c1bb6db8b49c428a53f00d', 0)
            ->payToAddress($value, AddressFactory::fromString('3EppTrJXEgNHgHoSRQdQaVQV4VS7Tg7aSs'))
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
        $keys = [
            PrivateKeyFactory::fromWif('KzzM4K74i3uoUKHZqfBRR44T1zcChzZFMjZkxZZReiTkSPkFv6jY'),
            PrivateKeyFactory::fromWif('L34yCtA8pZ2pGjdg2sKYJ5BwqW1rQYVNdSPMbRuCUfpJN9XkMqVR'),
            PrivateKeyFactory::fromWif('KxuuCULSevY215pnSPtRHka3YH83D9w2MKtwg1y33L6pBzk3tjQ1'),
        ];

        $multisig = ScriptFactory::scriptPubKey()->multisig(2, array_map(function (PrivateKeyInterface $key) {
            return $key->getPublicKey();
        }, $keys), false);

        $p2sh = new P2shScript($multisig);
        $addr = $p2sh->getAddress();

        $value = 40000;
        $txOut = new TransactionOutput($value, $addr->getScriptPubKey());

        $unsigned = (new TxBuilder())
            ->input('5077666f78045cb3482f64ee5d203366363af71436c1bb6db8b49c428a53f00d', 0)
            ->payToAddress($value, AddressFactory::fromString('3EppTrJXEgNHgHoSRQdQaVQV4VS7Tg7aSs'))
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

            $this->assertInstanceOf(\RuntimeException::class, $exception);
            $this->assertEquals("Padding is forbidden for a fully signed multisig script", $exception->getMessage());
        }
    }

    public function testFullySignedWitnessMultisigIsNotPadded()
    {
        $keys = [
            PrivateKeyFactory::fromWif('KzzM4K74i3uoUKHZqfBRR44T1zcChzZFMjZkxZZReiTkSPkFv6jY'),
            PrivateKeyFactory::fromWif('L34yCtA8pZ2pGjdg2sKYJ5BwqW1rQYVNdSPMbRuCUfpJN9XkMqVR'),
            PrivateKeyFactory::fromWif('KxuuCULSevY215pnSPtRHka3YH83D9w2MKtwg1y33L6pBzk3tjQ1'),
        ];

        $multisig = ScriptFactory::scriptPubKey()->multisig(2, array_map(function (PrivateKeyInterface $key) {
            return $key->getPublicKey();
        }, $keys), false);

        $p2wsh = new WitnessScript($multisig);

        $value = 40000;
        $txOut = new TransactionOutput($value, $p2wsh->getOutputScript());

        $unsigned = (new TxBuilder())
            ->input('5077666f78045cb3482f64ee5d203366363af71436c1bb6db8b49c428a53f00d', 0)
            ->payToAddress($value, AddressFactory::fromString('3EppTrJXEgNHgHoSRQdQaVQV4VS7Tg7aSs'))
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
                new ScriptWitness($copy)
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

            $this->assertInstanceOf(\RuntimeException::class, $exception);
            $this->assertEquals("Padding is forbidden for a fully signed multisig script", $exception->getMessage());
        }
    }
}
