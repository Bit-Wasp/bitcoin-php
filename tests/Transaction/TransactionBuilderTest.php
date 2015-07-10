<?php

namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Exceptions\BuilderNoInputState;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\MutableTransaction;
use BitWasp\Bitcoin\Transaction\MutableTransactionInput;
use BitWasp\Bitcoin\Transaction\MutableTransactionOutput;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionBuilder;
use BitWasp\Bitcoin\Script\Classifier\InputClassifier;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputCollection;
use BitWasp\Bitcoin\Utxo\Utxo;

class TransactionBuilderTest extends AbstractTestCase
{
    /**
     * @var string
     */
    public $txBldrType = 'BitWasp\Bitcoin\Transaction\TransactionBuilder';

    /**
     * @var string
     */
    public $txBldrStateType = 'BitWasp\Bitcoin\Transaction\TransactionBuilderInputState';

    public function testDefaultTransaction()
    {
        $tx = new Transaction();
        $ecAdapter = $this->safeEcAdapter();
        $builder = new TransactionBuilder($ecAdapter);

        $this->assertEquals($tx, $builder->getTransaction());
    }

    public function testCanAddOutput()
    {
        $output = new TransactionOutput(50, new Script());
        $ecAdapter = $this->safeEcAdapter();
        $builder = new TransactionBuilder($ecAdapter);
        $builder->addOutput($output);

        $this->assertEquals($output, $builder->getTransaction()->getOutputs()->getOutput(0));
    }

    public function testCanAddInput()
    {
        $input = new TransactionInput('5a4ebf66822b0b2d56bd9dc64ece0bc38ee7844a23ff1d7320a88c5fdb2ad3e2', 0);

        $ecAdapter = $this->safeEcAdapter();
        $builder = new TransactionBuilder($ecAdapter);
        $builder->addInput($input);

        $this->assertEquals($input, $builder->getTransaction()->getInputs()->getInput(0));
    }

    public function testSpendUtxo()
    {
        $txid = '5a4ebf66822b0b2d56bd9dc64ece0bc38ee7844a23ff1d7320a88c5fdb2ad3e2';
        $vout = 0;
        $utxo = new Utxo($txid, $vout, new TransactionOutput(1, new Script()));

        $ecAdapter = $this->safeEcAdapter();
        $builder = new TransactionBuilder($ecAdapter);
        $builder->spendUtxo($utxo);

        $input = $builder->getTransaction()->getInputs()->getInput(0);

        $this->assertEquals($txid, $input->getTransactionId());
        $this->assertEquals($vout, $input->getVout());
    }

    public function testTakesTransactionAsArgument()
    {
        $input = new TransactionInput('5a4ebf66822b0b2d56bd9dc64ece0bc38ee7844a23ff1d7320a88c5fdb2ad3e2', 0);
        $output = new TransactionOutput(50, new Script());

        $tx = new Transaction(
            Transaction::DEFAULT_VERSION,
            new TransactionInputCollection([$input]),
            new TransactionOutputCollection([$output])
        );

        $ecAdapter = $this->safeEcAdapter();
        $builder = new TransactionBuilder($ecAdapter, $tx);
        $this->assertEquals($tx, $builder->getTransaction());
        $this->assertEquals($input, $builder->getTransaction()->getInputs()->getInput(0));
        $this->assertEquals($output, $builder->getTransaction()->getOutputs()->getOutput(0));
    }

    public function testSpendsTxOut()
    {
        $input = new TransactionInput('5a4ebf66822b0b2d56bd9dc64ece0bc38ee7844a23ff1d7320a88c5fdb2ad3e2', 0);
        $output = new TransactionOutput(50, new Script());

        $tx = new Transaction(
            Transaction::DEFAULT_VERSION,
            new TransactionInputCollection([$input]),
            new TransactionOutputCollection([$output])
        );

        $txid = $tx->getTransactionId();
        $nOut = 0;

        $ecAdapter = $this->safeEcAdapter();
        $builder = new TransactionBuilder($ecAdapter);
        $builder->spendOutput($tx, $nOut);
        $this->assertEquals($txid, $builder->getTransaction()->getInputs()->getInput(0)->getTransactionId());
        $this->assertEquals($nOut, $builder->getTransaction()->getInputs()->getInput(0)->getVout());
    }

    public function getAddresses()
    {
        $key = PrivateKeyFactory::create();
        $script = ScriptFactory::multisig(1, [$key->getPublicKey()]);

        return [
            [$key->getAddress()],
            [$script->getAddress()],
        ];
    }

    /**
     * @dataProvider getAddresses
     * @param AddressInterface $address
     */
    public function testPayToAddress(AddressInterface $address)
    {
        $expectedScript = ScriptFactory::scriptPubKey()->payToAddress($address);

        $ecAdapter = $this->safeEcAdapter();
        $builder = new TransactionBuilder($ecAdapter);
        $builder->payToAddress($address, 50);

        $this->assertEquals($expectedScript, $builder->getTransaction()->getOutputs()->getOutput(0)->getScript());
    }

    public function getSampleTx()
    {
        return [
            [
                NetworkFactory::bitcoinTestnet(),
                '91mKqvjTfMQ1XiRYXt9YszSNWHNZ2D9RgcaKfNkiXQMwH2LnpmK',
                TransactionFactory::fromHex('010000000114a2856f5a2992a4ca0814be16a0ae79e2f88a6f53a20fcbcad5249165f56ee7010000006a47304402201e733603ac36239010e05ad229b4a18411d5507950f696db0771a5b7fe8e051202203c46da7e970e89cbbdfb4ee62fa775597a32e5029ab1d2a94f786999df2c2fd201210271127f11b833239aefd400b11d576e7cc48c6969c8e5f8e30b0f5ec0a514edf7feffffff02801a0600000000001976a914c4126d1b70f5667e492e3301c3aa8bf1031e21a888ac75a29d1d000000001976a9141ef8d6913c289890a5e9ec249fedde4440877d0288ac88540500'),
                false,
            ]
        ];
    }

    public function testSecp256k1RefusesRandomSigs()
    {
        if (extension_loaded('secp256k1')) {
            $math = $this->safeMath();
            $g = $this->safeGenerator();
            $secp256k1 = EcAdapterFactory::getSecp256k1($math, $g);
            $builder = new TransactionBuilder($secp256k1);
            try {
                $builder->useRandomSignatures();
                $this->fail('Exception was not thrown?');
            } catch (\RuntimeException $e) {
                $this->assertTrue(!!$e);
            }
        }
    }

    public function testSecp256k1VerifiablyDeterminstic()
    {
        if (extension_loaded('secp256k1')) {
            $math = $this->safeMath();
            $g = $this->safeGenerator();
            $secp256k1 = EcAdapterFactory::getSecp256k1($math, $g);
            $builder = new TransactionBuilder($secp256k1);

            $privateKey = PrivateKeyFactory::create();
            $outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPublicKey());
            $sampleSpendTx = new MutableTransaction();
            $sampleSpendTx->getInputs()->addInput(new TransactionInput('4141414141414141414141414141414141414141414141414141414141414141', 0));
            $sampleSpendTx->getOutputs()->addOutput(new TransactionOutput(
                50,
                $outputScript
            ));

            $builder->spendOutput($sampleSpendTx, 0);

            // Verify that repeatedly doing a deterministic signature yields the same result
            $this->compareTwoSignRuns($builder, $outputScript, $privateKey, true);

        }
    }

    public function testPhpeccVerifiablyRandomOrDeterministic()
    {
        $math = $this->safeMath();
        $g = $this->safeGenerator();
        $phpecc = EcAdapterFactory::getPhpEcc($math, $g);
        $builder = new TransactionBuilder($phpecc);

        $privateKey = PrivateKeyFactory::create();
        $outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPublicKey());
        $sampleSpendTx = new MutableTransaction();
        $sampleSpendTx->getInputs()->addInput(new TransactionInput('4141414141414141414141414141414141414141414141414141414141414141', 0));
        $sampleSpendTx->getOutputs()->addOutput(new TransactionOutput(
            50,
            $outputScript
        ));

        $builder->spendOutput($sampleSpendTx, 0);

        // Verify that repeatedly doing a deterministic signature yields the same result
        $this->compareTwoSignRuns($builder->useDeterministicSignatures(), $outputScript, $privateKey, true);

        // Switch to random signatures now.
        // They should not yield the same script each time. Assuming the only thing that can change is the signature..
        $this->compareTwoSignRuns($builder->useRandomSignatures(), $outputScript, $privateKey, false);

    }

    /**
     * @param TransactionBuilder $builder
     * @param ScriptInterface $outputScript
     * @param PrivateKeyInterface $privateKey
     * @param bool $expectedComparisonResult
     */
    private function compareTwoSignRuns(TransactionBuilder $builder, ScriptInterface $outputScript, PrivateKeyInterface $privateKey, $expectedComparisonResult)
    {
        $firstBuilder = clone ($builder);
        $firstBuilder->signInputWithKey($privateKey, $outputScript, 0);
        $firstScript = $firstBuilder->getTransaction()->getInputs()->getInput(0)->getScript();

        $anotherDetBuilder = clone ($builder);
        $anotherDetBuilder->signInputWithKey($privateKey, $outputScript, 0);
        $anotherDetScript = $anotherDetBuilder->getTransaction()->getInputs()->getInput(0)->getScript();

        $this->assertTrue($expectedComparisonResult === ($firstScript->getBinary() === $anotherDetScript->getBinary()));
    }

    public function testCanGetInputStateAfterSigning()
    {
        $pk1 = PrivateKeyFactory::create();
        $pk2 = PrivateKeyFactory::create();
        $redeemScript = ScriptFactory::multisig(2, [$pk1->getPublicKey(), $pk2->getPublicKey()]);

        $spendTx = new MutableTransaction();
        $spendTx->getInputs()->addInput(new TransactionInput(
            '4141414141414141414141414141414141414141414141414141414141414141',
            0
        ));
        $spendTx->getOutputs()->addOutput(new TransactionOutput(
            50,
            $redeemScript->getOutputScript()
        ));

        $ecAdapter = $this->safeEcAdapter();
        $builder = new TransactionBuilder($ecAdapter);
        $builder->spendOutput($spendTx, 0);

        $reachedException = false;
        try {
            $builder->getInputState(0);
        } catch (BuilderNoInputState $e) {
            $reachedException = true;
        }
        $this->assertTrue($reachedException, 'threw exception when there was no input state');

        $builder->signInputWithKey($pk1, $redeemScript->getOutputScript(), 0, $redeemScript);
        $reachedException = false;
        try {
            $builder->getInputState(0);
        } catch (BuilderNoInputState $e) {
            $reachedException = true;
        }

        $this->assertFalse($reachedException, "doesnt throw exception when input state is available");
    }

    public function testDoPayToPubkey()
    {
        $privateKey = PrivateKeyFactory::fromHex('f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b');
        $outputScript = ScriptFactory::scriptPubKey()->paytoPubKey($privateKey->getPublicKey());

        $spendTx = new MutableTransaction();
        $spendTx->getInputs(0)->addInput(new TransactionInput(
            '4141414141414141414141414141414141414141414141414141414141414141',
            0
        ));
        $spendTx->getOutputs(0)->addOutput(new TransactionOutput(
            50,
            $outputScript
        ));

        $ecAdapter = $this->safeEcAdapter();
        $builder = new TransactionBuilder($ecAdapter);
        $builder->spendOutput($spendTx, 0);
        $builder->signInputWithKey($privateKey, $outputScript, 0);
        $this->assertEquals(1, $builder->getInputState(0)->getRequiredSigCount());
        $this->assertEquals(1, $builder->getInputState(0)->getSigCount());
        $this->assertTrue($builder->getInputState(0)->isFullySigned());

        $this->assertEquals('0100000001e3733a6416659804465df063e4e080616af9052df29f9b0d40ac853c2d6ea2c000000000484730440220527c02eb17ff3bbe102b3d988a7258b0bdc32f07d0c86dfdcb1dd65708f222a402203bd00b0a524d3a592669a019bdebfd9fc243b913946b387c47415656ca6b735401ffffffff0000000000', $builder->getTransaction()->getHex());
    }

    public function testDoPayToPubkeyHash()
    {
        $privateKey = PrivateKeyFactory::fromHex('421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87');
        $outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPublicKey());

        $spendTx = new MutableTransaction();
        $spendTx->getInputs(0)->addInput(new TransactionInput(
            '4141414141414141414141414141414141414141414141414141414141414141',
            0
        ));
        $spendTx->getOutputs(0)->addOutput(new TransactionOutput(
            50,
            $outputScript
        ));

        $ecAdapter = $this->safeEcAdapter();
        $builder = new TransactionBuilder($ecAdapter);
        $builder->spendOutput($spendTx, 0);
        $builder->signInputWithKey($privateKey, $outputScript, 0);
        $this->assertEquals(1, $builder->getInputState(0)->getRequiredSigCount());
        $this->assertEquals(1, $builder->getInputState(0)->getSigCount());
        $this->assertTrue($builder->getInputState(0)->isFullySigned());

        $this->assertEquals('010000000149f6cfa59b303b017f976135857aec02ac480771db74cd4ae40bd4961dc59a96000000008b483045022100e1935063d5969a335fda631b8223a01a151ee7fe59a200e8fd348231ec925ec9022025ae1623b4a4e4dea9f5749f9263866fde4399c0ef51ac4f235782f37bc725db014104f260c8b554e9d0921c507fb231d0e226ba17462078825c56170facb6567dcec700750bd529f4361da21f59fbfc7d0bce319fdef4e7c524e82d3e313e92b1b347ffffffff0000000000', $builder->getTransaction()->getHex());
    }

    public function testDoMultisigP2SH()
    {
        $pk1 = PrivateKeyFactory::fromHex('421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87');
        $pk2 = PrivateKeyFactory::fromHex('f7225388c1d69d57e6251c9fda50cbbf9e05131e5adb81e5aa0422402f048162');

        $redeemScript = ScriptFactory::multisig(2, [$pk1->getPublicKey(), $pk2->getPublicKey()]);
        $outputScript = $redeemScript->getOutputScript();

        $spendTx = new MutableTransaction();
        $spendTx->getInputs(0)->addInput(new TransactionInput(
            '4141414141414141414141414141414141414141414141414141414141414141',
            0
        ));
        $spendTx->getOutputs(0)->addOutput(new TransactionOutput(
            50,
            $outputScript
        ));

        $ecAdapter = $this->safeEcAdapter();
        $builder = new TransactionBuilder($ecAdapter);
        $builder->spendOutput($spendTx, 0);
        $builder->signInputWithKey($pk1, $outputScript, 0, $redeemScript);
        $this->assertEquals(1, $builder->getInputState(0)->getSigCount());

        $builder->signInputWithKey($pk2, $outputScript, 0, $redeemScript);
        $this->assertEquals(2, $builder->getInputState(0)->getSigCount());
        $this->assertEquals(2, $builder->getInputState(0)->getRequiredSigCount());
        $this->assertTrue($builder->getInputState(0)->isFullySigned());

        $this->assertEquals('0100000001aafb9e229a0d5b18039724aa65c31eef2a1079210d38dc94b18e66cf84def84600000000fd1b0100483045022100a7fa1c1e7e37808175a2a17c913498623fb22c74a62605ee98c7c69d64425d3902204324efb27e5374b4b3e0637f58cac66b9e0349e18adfa176d20d50e3932a52910147304402205a490d36c2f26cbed936b2b35984eb6906e01918bee30e15a017703120360c3802201d3e0c02afccff356113e50f831a63b5bef1d913aee10731cf072b85c40cc12e014c8752410443f3ce7c4ddf438900a6662420511ea48321f8cedd3e63943700b07ac9752a6bf18230095730b18f2d3c3dbdc0a892ca62b1722730f183d370963d6f4d3e20c84104f260c8b554e9d0921c507fb231d0e226ba17462078825c56170facb6567dcec700750bd529f4361da21f59fbfc7d0bce319fdef4e7c524e82d3e313e92b1b34752aeffffffff0000000000', $builder->getTransaction()->getHex());
    }

    public function testIncrementallySigningP2PK()
    {
        $ecAdapter = $this->safeEcAdapter();

        $pk1 = PrivateKeyFactory::fromHex('421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87');
        $outputScript = ScriptFactory::scriptPubKey()->payToPubKey($pk1->getPublicKey());

        // This is the transaction we are pretending exists in the blockchain
        $spendTx = new MutableTransaction();
        $spendTx->getInputs(0)->addInput(new TransactionInput(
            '4141414141414141414141414141414141414141414141414141414141414141',
            0
        ));
        $spendTx->getOutputs(0)->addOutput(new TransactionOutput(
            50,
            $outputScript
        ));

        // Now we build a transaction spending it
        $builder = new TransactionBuilder($ecAdapter);
        $builder->spendOutput($spendTx, 0);
        $builder->payToAddress($pk1->getAddress(), 50);
        try {
            $builder->getInputState(0);
        } catch (BuilderNoInputState $e) {
            $this->assertTrue(!!$e);
        }

        // Take the built transaction, pass it to a new TransactionBuilder class, and manually create input state
        $builder = new TransactionBuilder($ecAdapter, TransactionFactory::fromHex($builder->getTransaction()->getHex()));
        $builder->createInputState(0, $outputScript);

        // Check InputState was correctly set up
        $is = $builder->getInputState(0);
        $this->assertEquals(InputClassifier::PAYTOPUBKEY, $is->getScriptType());
        $this->assertInstanceOf($this->txBldrStateType, $is);
        $this->assertEquals(0, $is->getSigCount());
        $this->assertEquals(1, $is->getRequiredSigCount());

        // Check input is correctly set up
        $i = $builder->getTransaction()->getInputs()->getInput(0);
        $this->assertEquals($spendTx->getTransactionId(), $i->getTransactionId());
        $this->assertEquals(0, $i->getVout());
        $this->assertEquals(new Script(), $i->getScript());

        // Check output is correctly set up
        $o = $builder->getTransaction()->getOutputs()->getOutput(0);
        $this->assertEquals(50, $o->getValue());
        $this->assertEquals(ScriptFactory::scriptPubKey()->payToPubKeyHash($pk1->getPublicKey()), $o->getScript());

        // Do signing
        $builder->signInputWithKey($pk1, $outputScript, 0);

        // Check that all signatures and state were preserved
        $builder = new TransactionBuilder($ecAdapter, $builder->getTransaction());
        $builder->createInputState(0, $outputScript);
        $this->assertEquals(1, $builder->getInputState(0)->getSigCount());
        $this->assertTrue($builder->getInputState(0)->isFullySigned());
        $this->assertTrue($builder->isFullySigned());
    }

    public function testIncrementallySigningP2PKH()
    {
        $ecAdapter = $this->safeEcAdapter();

        $pk1 = PrivateKeyFactory::fromHex('421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87');
        $outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($pk1->getPublicKey());

        // This is the transaction we are pretending exists in the blockchain
        $spendTx = new MutableTransaction();
        $spendTx->getInputs(0)->addInput(new TransactionInput(
            '4141414141414141414141414141414141414141414141414141414141414141',
            0
        ));
        $spendTx->getOutputs(0)->addOutput(new TransactionOutput(
            50,
            $outputScript
        ));

        // Now we build a transaction spending it
        $builder = new TransactionBuilder($ecAdapter);
        $builder->spendOutput($spendTx, 0);
        $builder->payToAddress($pk1->getAddress(), 50);
        try {
            $builder->getInputState(0);
        } catch (BuilderNoInputState $e) {
            $this->assertTrue(!!$e);
        }

        // Take the built transaction, pass it to a new TransactionBuilder class, and manually create input state
        $builder = new TransactionBuilder($ecAdapter, TransactionFactory::fromHex($builder->getTransaction()->getHex()));
        $builder->createInputState(0, $outputScript);

        // Check InputState was correctly set up
        $is = $builder->getInputState(0);

        $this->assertInstanceOf($this->txBldrStateType, $is);
        $this->assertEquals(InputClassifier::PAYTOPUBKEYHASH, $is->getScriptType());
        $this->assertEquals(0, $is->getSigCount());
        $this->assertEquals(1, $is->getRequiredSigCount());
        $this->assertFalse($is->isFullySigned());

        // Check input is correctly set up
        $i = $builder->getTransaction()->getInputs()->getInput(0);
        $this->assertEquals($spendTx->getTransactionId(), $i->getTransactionId());
        $this->assertEquals(0, $i->getVout());
        $this->assertEquals(new Script(), $i->getScript());

        // Check output is correctly set up
        $o = $builder->getTransaction()->getOutputs()->getOutput(0);
        $this->assertEquals(50, $o->getValue());
        $this->assertEquals(ScriptFactory::scriptPubKey()->payToPubKeyHash($pk1->getPublicKey()), $o->getScript());

        // Do signing
        $builder->signInputWithKey($pk1, $outputScript, 0);

        // Check that all signatures and state were preserved
        $builder = new TransactionBuilder($ecAdapter, $builder->getTransaction());
        $builder->createInputState(0, $outputScript);
        $this->assertEquals(1, $builder->getInputState(0)->getSigCount());
        $this->assertTrue($builder->getInputState(0)->isFullySigned());
        $this->assertTrue($builder->isFullySigned());
    }

    public function testIncrementallySigningP2SHMultisig()
    {
        $ecAdapter = $this->safeEcAdapter();

        $pk1 = PrivateKeyFactory::fromHex('421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87');
        $pk2 = PrivateKeyFactory::fromHex('f7225388c1d69d57e6251c9fda50cbbf9e05131e5adb81e5aa0422402f048162');

        $redeemScript = ScriptFactory::multisig(2, [$pk1->getPublicKey(), $pk2->getPublicKey()]);
        $outputScript = $redeemScript->getOutputScript();

        $spendTx = new MutableTransaction();
        $spendTx->getInputs(0)->addInput(new TransactionInput(
            '4141414141414141414141414141414141414141414141414141414141414141',
            0
        ));
        $spendTx->getOutputs(0)->addOutput(new TransactionOutput(
            50,
            $outputScript
        ));

        $builder = new TransactionBuilder($ecAdapter);
        $builder->spendOutput($spendTx, 0);
        $builder->payToAddress($pk1->getAddress(), 50);
        try {
            $builder->getInputState(0);
        } catch (BuilderNoInputState $e) {
            $this->assertTrue(!!$e);
        }

        $builder = new TransactionBuilder($ecAdapter, TransactionFactory::fromHex($builder->getTransaction()->getHex()), $ecAdapter);

        $builder->createInputState(0, $outputScript, $redeemScript);
        $is = $builder->getInputState(0);
        $this->assertInstanceOf($this->txBldrStateType, $is);
        $this->assertEquals(InputClassifier::MULTISIG, $is->getScriptType());
        $this->assertEquals(InputClassifier::PAYTOSCRIPTHASH, $is->getPrevOutType());
        $this->assertEquals(0, $is->getSigCount());

        // Check input is correctly set up
        $i = $builder->getTransaction()->getInputs()->getInput(0);
        $this->assertEquals($spendTx->getTransactionId(), $i->getTransactionId());
        $this->assertEquals(0, $i->getVout());
        $this->assertEquals(new Script(), $i->getScript());

        // Check output is correctly set up
        $o = $builder->getTransaction()->getOutputs()->getOutput(0);
        $this->assertEquals(50, $o->getValue());
        $this->assertEquals(ScriptFactory::scriptPubKey()->payToPubKeyHash($pk1->getPublicKey()), $o->getScript());

        // Have the builder sign
        $builder->signInputWithKey($pk1, $outputScript, 0, $redeemScript);

        // Import the 1/2 signed transaction
        $builder = new TransactionBuilder($ecAdapter, TransactionFactory::fromHex($builder->getTransaction()->getHex()), $ecAdapter);
        $builder->signInputWithKey($pk2, $outputScript, 0, $redeemScript);
        $this->assertEquals(2, $builder->getInputState(0)->getSigCount());
        $hex = $builder->getTransaction()->getHex();
        $t = TransactionFactory::fromHex($hex);

        // Check that all signatures and state can be extracted.
        $builder = new TransactionBuilder($ecAdapter, $t);
        $builder->createInputState(0, $outputScript, $redeemScript);
        $this->assertEquals(2, $builder->getInputState(0)->getSigCount());
        $this->assertTrue($builder->getInputState(0)->isFullySigned());
        $this->assertTrue($builder->isFullySigned());
    }
}
