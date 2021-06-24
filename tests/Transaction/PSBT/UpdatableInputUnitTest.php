<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction\PSBT;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Exceptions\InvalidPSBTException;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Script\WitnessScript;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\PSBT\PSBT;
use BitWasp\Bitcoin\Transaction\PSBT\PSBTBip32Derivation;
use BitWasp\Bitcoin\Transaction\PSBT\PSBTInput;
use BitWasp\Bitcoin\Transaction\PSBT\PSBTOutput;
use BitWasp\Bitcoin\Transaction\PSBT\UpdatableInput;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

class UpdatableInputUnitTest extends AbstractTestCase
{
    const TEST_BIP32_ENTROPY = "01020304010203040102030401020304";
    const TEST_HD_PATH = [1<<31|44,];

    private function getHdRootKey(EcAdapterInterface $ecAdapter): HierarchicalKey
    {
        $hdFactory = new HierarchicalKeyFactory($ecAdapter);
        return $hdFactory->fromEntropy(Buffer::hex(self::TEST_BIP32_ENTROPY));
    }

    private function getHdChildKey(HierarchicalKey $hdRoot): HierarchicalKey
    {
        $child = $hdRoot;
        foreach (self::TEST_HD_PATH as $index) {
            $child = $child->deriveChild($index);
        }
        return $child;
    }

    private function buildTransaction(array $outPoints, array $outputs): TransactionInterface
    {
        $inputs = [];
        foreach ($outPoints as $outPoint) {
            $inputs[] = new TransactionInput($outPoint, new Script());
        }
        return new Transaction(1, $inputs, $outputs, [], 0);
    }

    public function testReturnInput()
    {
        $ecAdapter = Bitcoin::getEcAdapter();
        $child = $this->getHdChildKey($this->getHdRootKey($ecAdapter));
        $pubKey = $child->getPublicKey()->getBuffer();
        $p2pkh = ScriptFactory::scriptPubKey()->p2pkh(Hash::sha256ripe160($pubKey));

        $unsignedTx = $this->buildTransaction(
            [new OutPoint(Buffer::hex("01", 32), 0x01)],
            [new TransactionOutput(100000000, $p2pkh),]
        );

        $input = new PSBTInput();
        $psbt = new PSBT(
            $unsignedTx,
            [],
            [$input],
            [new PSBTOutput()]
        );

        $updatableInput = new UpdatableInput($psbt, 0, $psbt->getInputs()[0]);
        $this->assertSame($input, $updatableInput->input());
    }

    public function testAddNonWitnessTx()
    {
        $ecAdapter = Bitcoin::getEcAdapter();
        $child = $this->getHdChildKey($this->getHdRootKey($ecAdapter));
        $pubKey = $child->getPublicKey()->getBuffer();
        $p2pkh = ScriptFactory::scriptPubKey()->p2pkh(Hash::sha256ripe160($pubKey));

        $fundTx = new Transaction(0, [new TransactionInput(new OutPoint(Buffer::hex("01", 32), 0x01), new Script())], [new TransactionOutput(100001000, $p2pkh)]);

        $unsignedTx = $this->buildTransaction(
            [$fundTx->makeOutpoint(0)],
            [new TransactionOutput(100000000, $p2pkh),]
        );

        $input = new PSBTInput();
        $psbt = new PSBT(
            $unsignedTx,
            [],
            [$input],
            [new PSBTOutput()]
        );

        $updatableInput = new UpdatableInput($psbt, 0, $psbt->getInputs()[0]);
        try {
            $updatableInput->input()->getNonWitnessTx();
        } catch (InvalidPSBTException $e) {
            $this->assertEquals("Transaction not known", $e->getMessage());
        }

        $updatableInput->addNonWitnessTx($fundTx);
        $this->assertSame($fundTx, $updatableInput->input()->getNonWitnessTx());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage unsigned tx outpoint does not exist in this transaction
     */
    public function testAddNonWitnessTxWithoutOutput()
    {
        $ecAdapter = Bitcoin::getEcAdapter();
        $child = $this->getHdChildKey($this->getHdRootKey($ecAdapter));
        $pubKey = $child->getPublicKey()->getBuffer();
        $p2pkh = ScriptFactory::scriptPubKey()->p2pkh(Hash::sha256ripe160($pubKey));

        $fundTx = new Transaction(0, [new TransactionInput(new OutPoint(Buffer::hex("01", 32), 0x01), new Script())], []);

        $unsignedTx = $this->buildTransaction(
            [new OutPoint($fundTx->getTxId(), 0)],
            [new TransactionOutput(100000000, $p2pkh),]
        );

        $input = new PSBTInput();
        $psbt = new PSBT(
            $unsignedTx,
            [],
            [$input],
            [new PSBTOutput()]
        );

        $updatableInput = new UpdatableInput($psbt, 0, $psbt->getInputs()[0]);
        $updatableInput->addNonWitnessTx($fundTx);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Non-witness txid differs from unsigned tx input 0
     */
    public function testAddNonWitnessTxWithWrongTx()
    {
        $ecAdapter = Bitcoin::getEcAdapter();
        $child = $this->getHdChildKey($this->getHdRootKey($ecAdapter));
        $pubKey = $child->getPublicKey()->getBuffer();
        $p2pkh = ScriptFactory::scriptPubKey()->p2pkh(Hash::sha256ripe160($pubKey));

        $fundTx = new Transaction(0, [new TransactionInput(new OutPoint(Buffer::hex("01", 32), 0x01), new Script())], []);

        $unsignedTx = $this->buildTransaction(
            [new OutPoint(new Buffer("spend a different tx hash", 32), 0)],
            [new TransactionOutput(100000000, $p2pkh),]
        );

        $input = new PSBTInput();
        $psbt = new PSBT(
            $unsignedTx,
            [],
            [$input],
            [new PSBTOutput()]
        );

        $updatableInput = new UpdatableInput($psbt, 0, $psbt->getInputs()[0]);
        $updatableInput->addNonWitnessTx($fundTx);
    }

    public function testAddWitnessTxOut()
    {
        $ecAdapter = Bitcoin::getEcAdapter();
        $child = $this->getHdChildKey($this->getHdRootKey($ecAdapter));
        $pubKey = $child->getPublicKey()->getBuffer();
        $p2pkh = ScriptFactory::scriptPubKey()->p2pkh(Hash::sha256ripe160($pubKey));

        $fundTx = new Transaction(0, [new TransactionInput(new OutPoint(Buffer::hex("01", 32), 0x01), new Script())], [new TransactionOutput(100001000, $p2pkh)], [new ScriptWitness()]);
        $spendVout = 0;
        $unsignedTx = $this->buildTransaction(
            [$fundTx->makeOutpoint($spendVout)],
            [new TransactionOutput(100000000, $p2pkh),]
        );

        $input = new PSBTInput();
        $psbt = new PSBT(
            $unsignedTx,
            [],
            [$input],
            [new PSBTOutput()]
        );

        $updatableInput = new UpdatableInput($psbt, 0, $psbt->getInputs()[0]);
        try {
            $updatableInput->input()->getWitnessTxOut();
        } catch (InvalidPSBTException $e) {
            $this->assertEquals("Witness txout not known", $e->getMessage());
        }

        $fundOutput = $fundTx->getOutput($spendVout);
        $updatableInput->addWitnessTxOut($fundOutput);
        $this->assertSame($fundOutput, $updatableInput->input()->getWitnessTxOut());
    }

    public function testAddRedeemScript()
    {
        $pubKey = Buffer::hex("03e495306fca12c490e63353320b38d24786a68794384f0a6cea6838c976b2ce58");
        $p2pkh = ScriptFactory::scriptPubKey()->p2pkh(Hash::sha256ripe160($pubKey));
        $fundSpk = (new P2shScript($p2pkh))->getOutputScript();

        $unsignedTx = $this->buildTransaction(
            [new OutPoint(Buffer::hex("01", 32), 0x01)],
            [new TransactionOutput(100000000, $fundSpk),]
        );

        $input = new PSBTInput();
        $psbt = new PSBT(
            $unsignedTx,
            [],
            [$input],
            [new PSBTOutput()]
        );

        $updatableInput = new UpdatableInput($psbt, 0, $psbt->getInputs()[0]);
        try {
            $updatableInput->input()->getRedeemScript();
        } catch (InvalidPSBTException $e) {
            $this->assertEquals("Redeem script not known", $e->getMessage());
        }

        $updatableInput->addRedeemScript($p2pkh);
        $this->assertSame($p2pkh, $updatableInput->input()->getRedeemScript());
    }

    public function testAddWitnessScript()
    {
        $pubKey = Buffer::hex("03e495306fca12c490e63353320b38d24786a68794384f0a6cea6838c976b2ce58");
        $p2pkh = ScriptFactory::scriptPubKey()->p2pkh(Hash::sha256ripe160($pubKey));
        $fundSpk = (new WitnessScript($p2pkh))->getOutputScript();

        $unsignedTx = $this->buildTransaction(
            [new OutPoint(Buffer::hex("01", 32), 0x01)],
            [new TransactionOutput(100000000, $fundSpk),]
        );

        $input = new PSBTInput();
        $psbt = new PSBT(
            $unsignedTx,
            [],
            [$input],
            [new PSBTOutput()]
        );

        $updatableInput = new UpdatableInput($psbt, 0, $psbt->getInputs()[0]);
        try {
            $updatableInput->input()->getWitnessScript();
        } catch (InvalidPSBTException $e) {
            $this->assertEquals("Witness script not known", $e->getMessage());
        }

        $updatableInput->addWitnessScript($p2pkh);
        $this->assertSame($p2pkh, $updatableInput->input()->getWitnessScript());
    }

    public function testAddDerivation()
    {
        $ecAdapter = Bitcoin::getEcAdapter();
        $pubKey = Buffer::hex("03e495306fca12c490e63353320b38d24786a68794384f0a6cea6838c976b2ce58");
        $keySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, false, $ecAdapter);
        $realPubKey = $keySerializer->parse($pubKey);
        $p2pkh = ScriptFactory::scriptPubKey()->p2pkh(Hash::sha256ripe160($pubKey));
        $fundSpk = (new WitnessScript($p2pkh))->getOutputScript();
        $derivation = new PSBTBip32Derivation($pubKey, 0x01020304, ...[0, 0]);

        $unsignedTx = $this->buildTransaction(
            [new OutPoint(Buffer::hex("01", 32), 0x01)],
            [new TransactionOutput(100000000, $fundSpk),]
        );

        $input = new PSBTInput();
        $psbt = new PSBT(
            $unsignedTx,
            [],
            [$input],
            [new PSBTOutput()]
        );

        $updatableInput = new UpdatableInput($psbt, 0, $psbt->getInputs()[0]);
        $this->assertCount(0, $updatableInput->input()->getBip32Derivations());

        $updatableInput->addDerivation($realPubKey, $derivation);
        $this->assertCount(1, $updatableInput->input()->getBip32Derivations());
    }
}
