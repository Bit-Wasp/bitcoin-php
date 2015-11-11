<?php

namespace BitWasp\Bitcoin\Tests\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Exceptions\BuilderNoInputState;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\Factory\TxSigner;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;

class TxSignerTest extends AbstractTestCase
{

    public function testSecp256k1VerifiablyDeterminstic()
    {
        if (extension_loaded('secp256k1')) {
            $math = $this->safeMath();
            $g = $this->safeGenerator();
            $secp256k1 = EcAdapterFactory::getSecp256k1($math, $g);

            $privateKey = PrivateKeyFactory::create();
            $outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPublicKey());

            $sampleSpendTx = (new TxBuilder())
                ->output(50, $outputScript)
                ->get()
            ;

            $forBuilder = (new TxBuilder())
                ->spendOutputFrom($sampleSpendTx, 0)
                ->get();

            $builder = new TxSigner($secp256k1, $forBuilder);

            // Verify that repeatedly doing a deterministic signature yields the same result
            $this->compareTwoSignRuns($builder, $outputScript, $privateKey, true);

        }
    }
    /**
     * @param TxSigner $builder
     * @param ScriptInterface $outputScript
     * @param PrivateKeyInterface $privateKey
     * @param bool $expectedComparisonResult
     */
    private function compareTwoSignRuns(TxSigner $builder, ScriptInterface $outputScript, PrivateKeyInterface $privateKey, $expectedComparisonResult)
    {
        $firstBuilder = clone ($builder);
        $firstBuilder->sign(0, $privateKey, $outputScript);
        $firstScript = $firstBuilder->get()->getInput(0)->getScript();

        $anotherDetBuilder = clone ($builder);
        $anotherDetBuilder->sign(0, $privateKey, $outputScript);
        $anotherDetScript = $anotherDetBuilder->get()->getInput(0)->getScript();

        $this->assertTrue($expectedComparisonResult === ($firstScript->getBinary() === $anotherDetScript->getBinary()));
    }

    public function testPhpeccVerifiablyRandomOrDeterministic()
    {
        $math = $this->safeMath();
        $g = $this->safeGenerator();
        $phpecc = EcAdapterFactory::getPhpEcc($math, $g);

        $privateKey = $phpecc->getPrivateKey(1);
        $outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPublicKey());

        $sampleSpendTx = (new TxBuilder())
            ->output(50, $outputScript)
            ->get()
        ;

        $forBuilder = (new TxBuilder())
            ->spendOutputFrom($sampleSpendTx, 0)
            ->get();

        $builder = new TxSigner($phpecc, $forBuilder);

        // Verify that repeatedly doing a deterministic signature yields the same result
        $this->compareTwoSignRuns($builder->useDeterministicSignatures(), $outputScript, $privateKey, true);

        // Switch to random signatures now.
        // They should not yield the same script each time. Assuming the only thing that can change is the signature..
        $this->compareTwoSignRuns($builder->useRandomSignatures(), $outputScript, $privateKey, false);

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

            $builder = new TxSigner($secp256k1, new Transaction());
            try {
                $builder->useRandomSignatures();
                $this->fail('Exception was not thrown?');
            } catch (\RuntimeException $e) {
                $this->assertTrue(!!$e);
            }
        }
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testCanGetInputStateAfterSigning(EcAdapterInterface $ecAdapter)
    {
        $pk1 = PrivateKeyFactory::create(false, $ecAdapter);
        $pk2 = PrivateKeyFactory::create(false, $ecAdapter);
        $redeemScript = ScriptFactory::multisigNew(2, [$pk1->getPublicKey(), $pk2->getPublicKey()]);
        $outputScript = ScriptFactory::scriptPubKey()->payToScriptHash($redeemScript);
        $sampleSpendTx = (new TxBuilder())
            ->output(50, $outputScript)
            ->get()
        ;

        $forBuilder = (new TxBuilder())
            ->spendOutputFrom($sampleSpendTx, 0)
            ->get();

        $signer = new TxSigner($ecAdapter, $forBuilder);
        $reachedException = false;
        try {
            $signer->inputState(0);
        } catch (BuilderNoInputState $e) {
            $reachedException = true;
        }
        $this->assertTrue($reachedException, 'threw exception when there was no input state');

        $signer->sign(0, $pk1, $outputScript, $redeemScript);
        $reachedException = false;
        try {
            $signer->inputState(0);
        } catch (BuilderNoInputState $e) {
            $reachedException = true;
        }

        $this->assertFalse($reachedException, "doesnt throw exception when input state is available");
    }

    /**
     * @dataProvider getEcAdapters
     * @throws BuilderNoInputState
     * @param EcAdapterInterface $ecAdapter
     */
    public function testDoPayToPubkey(EcAdapterInterface $ecAdapter)
    {
        $privateKey = PrivateKeyFactory::fromHex('f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b', false, $ecAdapter);
        $outputScript = ScriptFactory::scriptPubKey()->payToPubKey($privateKey->getPublicKey());

        $sampleSpendTx = (new TxBuilder())
            ->input('4141414141414141414141414141414141414141414141414141414141414141', 0)
            ->output(50, $outputScript)
            ->get()
        ;

        $forBuilder = (new TxBuilder())
            ->spendOutputFrom($sampleSpendTx, 0)
            ->get();

        $builder = new TxSigner($ecAdapter, $forBuilder);
        $builder->sign(0, $privateKey, $outputScript);
        $this->assertEquals(1, $builder->inputState(0)->getRequiredSigCount());
        $this->assertEquals(1, $builder->inputState(0)->getSigCount());
        $this->assertTrue($builder->inputState(0)->isFullySigned());

        $this->assertEquals('0100000001e3733a6416659804465df063e4e080616af9052df29f9b0d40ac853c2d6ea2c000000000484730440220527c02eb17ff3bbe102b3d988a7258b0bdc32f07d0c86dfdcb1dd65708f222a402203bd00b0a524d3a592669a019bdebfd9fc243b913946b387c47415656ca6b735401ffffffff0000000000', $builder->get()->getHex());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws BuilderNoInputState
     */
    public function testDoPayToPubkeyHash(EcAdapterInterface $ecAdapter)
    {
        $privateKey = PrivateKeyFactory::fromHex('421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87', false, $ecAdapter);
        $outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPublicKey());

        $sampleSpendTx = (new TxBuilder())
            ->input('4141414141414141414141414141414141414141414141414141414141414141', 0)
            ->output(50, $outputScript)
            ->get()
        ;

        $forBuilder = (new TxBuilder())
            ->spendOutputFrom($sampleSpendTx, 0)
            ->get();

        $signer = new TxSigner($ecAdapter, $forBuilder);
        $signer->sign(0, $privateKey, $outputScript);
        $state = $signer->inputState(0);
        $this->assertEquals(1, $state->getRequiredSigCount());
        $this->assertEquals(1, $state->getSigCount());
        $this->assertTrue($signer->inputState(0)->isFullySigned());

        $this->assertEquals('010000000149f6cfa59b303b017f976135857aec02ac480771db74cd4ae40bd4961dc59a96000000008b483045022100e1935063d5969a335fda631b8223a01a151ee7fe59a200e8fd348231ec925ec9022025ae1623b4a4e4dea9f5749f9263866fde4399c0ef51ac4f235782f37bc725db014104f260c8b554e9d0921c507fb231d0e226ba17462078825c56170facb6567dcec700750bd529f4361da21f59fbfc7d0bce319fdef4e7c524e82d3e313e92b1b347ffffffff0000000000', $signer->get()->getHex());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testDoMultisigP2SH(EcAdapterInterface $ecAdapter)
    {
        $pk1 = PrivateKeyFactory::fromHex('421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87', false, $ecAdapter);
        $pk2 = PrivateKeyFactory::fromHex('f7225388c1d69d57e6251c9fda50cbbf9e05131e5adb81e5aa0422402f048162', false, $ecAdapter);

        $redeemScript = ScriptFactory::multisig(2, [$pk1->getPublicKey(), $pk2->getPublicKey()]);
        $outputScript = ScriptFactory::scriptPubKey()->payToScriptHash($redeemScript);

        $sampleSpendTx = (new TxBuilder())
            ->input('4141414141414141414141414141414141414141414141414141414141414141', 0)
            ->output(50, $outputScript)
            ->get()
        ;

        $forBuilder = (new TxBuilder())
            ->spendOutputFrom($sampleSpendTx, 0)
            ->get();

        $signer = new TxSigner($ecAdapter, $forBuilder);
        $signer->sign(0, $pk1, $outputScript, $redeemScript);
        $signer->sign(0, $pk2, $outputScript, $redeemScript);

        $this->assertEquals('0100000001aafb9e229a0d5b18039724aa65c31eef2a1079210d38dc94b18e66cf84def84600000000fd1b0100483045022100a7fa1c1e7e37808175a2a17c913498623fb22c74a62605ee98c7c69d64425d3902204324efb27e5374b4b3e0637f58cac66b9e0349e18adfa176d20d50e3932a52910147304402205a490d36c2f26cbed936b2b35984eb6906e01918bee30e15a017703120360c3802201d3e0c02afccff356113e50f831a63b5bef1d913aee10731cf072b85c40cc12e014c8752410443f3ce7c4ddf438900a6662420511ea48321f8cedd3e63943700b07ac9752a6bf18230095730b18f2d3c3dbdc0a892ca62b1722730f183d370963d6f4d3e20c84104f260c8b554e9d0921c507fb231d0e226ba17462078825c56170facb6567dcec700750bd529f4361da21f59fbfc7d0bce319fdef4e7c524e82d3e313e92b1b34752aeffffffff0000000000', $signer->get()->getHex());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testIncrementallySigningP2PK(EcAdapterInterface $ecAdapter)
    {
        $pk1 = PrivateKeyFactory::fromHex('421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87', false, $ecAdapter);
        $outputScript = ScriptFactory::scriptPubKey()->payToPubKey($pk1->getPublicKey());

        // This is the transaction we are pretending exists in the blockchain

        $spendTx = (new TxBuilder())
            ->output(50, $outputScript)
            ->get()
        ;

        $forBuilder = (new TxBuilder())
            ->spendOutputFrom($spendTx, 0)
            ->payToAddress(50, $pk1->getAddress())
            ->get();

        // Take the built transaction, pass it to a new TransactionBuilder class, and manually create input state
        $builder = new TxSigner($ecAdapter, $forBuilder);

        // Do signing
        $builder->sign(0, $pk1, $outputScript);

        $this->assertTrue($builder->isFullySigned());
    }

    public function testIncrementallySigningP2PKH()
    {
        $ecAdapter = $this->safeEcAdapter();

        $pk1 = PrivateKeyFactory::fromHex('421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87', false, $ecAdapter);
        $outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($pk1->getPublicKey());

        // This is the transaction we are pretending exists in the blockchain

        $spendTx = (new TxBuilder())
            ->output(50, $outputScript)
            ->get()
        ;

        $forBuilder = (new TxBuilder())
            ->spendOutputFrom($spendTx, 0)
            ->payToAddress(50, $pk1->getAddress())
            ->get();

        // Now we build a transaction spending it
        $builder = new TxSigner($ecAdapter, $forBuilder);
        try {
            $builder->inputState(0);
        } catch (BuilderNoInputState $e) {
            $this->assertTrue(!!$e);
        }

        // Take the built transaction, pass it to a new TransactionBuilder class, and manually create input state
        $builder = new TxSigner($ecAdapter, TransactionFactory::fromHex($builder->get()->getHex()));

        // Do signing
        $builder->sign(0, $pk1, $outputScript);

        $this->assertEquals(1, $builder->inputState(0)->getSigCount());
        $this->assertTrue($builder->inputState(0)->isFullySigned());
        $this->assertTrue($builder->isFullySigned());
    }

    public function testIncrementallySigningP2SHMultisig()
    {
        $ecAdapter = $this->safeEcAdapter();

        $pk1 = PrivateKeyFactory::fromHex('421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87');
        $pk2 = PrivateKeyFactory::fromHex('f7225388c1d69d57e6251c9fda50cbbf9e05131e5adb81e5aa0422402f048162');

        $redeemScript = ScriptFactory::multisig(2, [$pk1->getPublicKey(), $pk2->getPublicKey()]);
        $outputScript = ScriptFactory::scriptPubKey()->payToScriptHash($redeemScript);

        // this is the transaction we are pretending exists in the blockchain
        $spendTx = (new TxBuilder())
            ->output(50, $outputScript)
            ->get()
        ;

        $forBuilder = (new TxBuilder())
            ->spendOutputFrom($spendTx, 0)
            ->payToAddress(50, $pk1->getAddress())
            ->get();

        // Now we build a transaction spending it
        $signer = new TxSigner($ecAdapter, $forBuilder);
        try {
            $signer->inputState(0);
        } catch (BuilderNoInputState $e) {
            $this->assertTrue(!!$e);
        }

        $signer = new TxSigner($ecAdapter, TransactionFactory::fromHex($signer->get()->getHex()), $ecAdapter);

        // Have the builder sign
        $signer->sign(0, $pk1, $outputScript, $redeemScript);

        // Import the 1/2 signed transaction
        $signer = new TxSigner($ecAdapter, TransactionFactory::fromHex($signer->get()->getHex()), $ecAdapter);
        $signer->sign(0, $pk2, $outputScript, $redeemScript);
        $this->assertEquals(2, $signer->inputState(0)->getSigCount());
        $this->assertTrue($signer->inputState(0)->isFullySigned());
        $this->assertTrue($signer->isFullySigned());
    }
}
