<?php

namespace BitWasp\Bitcoin\Tests\Transaction\Factory;


use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHashInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;

class TxSigningTest extends AbstractTestCase
{
    public function buildCreditingTransaction(EcAdapterInterface $ecAdapter, ScriptInterface $scriptPubKey)
    {
        $privateKey = PrivateKeyFactory::create(true, $ecAdapter);
        $firstAmount = 5000000001;
        $firstSPK = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPublicKey());
        $utxo = new Utxo(new OutPoint(new Buffer('', 32), 0), new TransactionOutput($firstAmount, $firstSPK));

        $builder = new TxBuilder();
        $builder->spendOutPoint($utxo->getOutPoint());
        $builder->output(5000000000, $scriptPubKey);
        $unspent = $builder->get();

        $signer = new Signer($unspent, $ecAdapter);
        $signer->sign(0, $privateKey, $utxo->getOutput());
        $signed = $signer->get();

        return $signed;
    }

    public function buildTest(EcAdapterInterface $ecAdapter, ScriptInterface $scriptPubKey)
    {

    }

    public function createTests()
    {
        $ecAdapter = Bitcoin::getEcAdapter();
        $testKey = PrivateKeyFactory::create(true, $ecAdapter);
        $otherKey = PrivateKeyFactory::create(true, $ecAdapter);
        $P2PK = ScriptFactory::scriptPubKey()->payToPubKey($testKey->getPublicKey());
        $P2PKH = ScriptFactory::scriptPubKey()->payToPubKeyHash($testKey->getPublicKey());
        $multisig = ScriptFactory::scriptPubKey()->multisig(1, [$testKey->getPublicKey(), $otherKey->getPublicKey()], true);
        $P2SH_P2PK = new P2shScript($P2PK);
        $P2SH_P2PKH = new P2shScript($P2PKH);
        $P2SH_Multisig = new P2shScript($multisig);
        $P2WPKH = ScriptFactory::sequence([Opcodes::OP_0, $testKey->getPublicKey()->getPubKeyHash()]);
        $P2WSH_P2PK = ScriptFactory::sequence([Opcodes::OP_0, Hash::sha256($P2PK->getBuffer())]);
        $P2WSH_P2PKH = ScriptFactory::sequence([Opcodes::OP_0, Hash::sha256($P2PKH->getBuffer())]);
        $P2WSH_Multisig = ScriptFactory::sequence([Opcodes::OP_0, Hash::sha256($multisig->getBuffer())]);
        $P2SH_P2WPKH = new P2shScript($P2WPKH);
        $P2SH_P2WSH = new P2shScript($P2WSH_Multisig);

        $flags = InterpreterInterface::VERIFY_P2SH | Interpreter::VERIFY_WITNESS;
        $tests = [];
        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $P2PK), $testKey, $flags, 0xffffffff, SigHashInterface::ALL, null, null];
        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $P2PK), $testKey, $flags, 0xffffffff, SigHashInterface::SINGLE, null, null];
        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $P2PK), $testKey, $flags, 0xffffffff, SigHashInterface::NONE, null, null];

        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $P2PKH), $testKey, $flags, 0xffffffff, SigHashInterface::ALL, null, null];
        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $multisig), $testKey, $flags, 0xffffffff, SigHashInterface::ALL, null, null];

        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $P2SH_P2PK), $testKey, $flags, 0xffffffff, SigHashInterface::ALL, $P2PK, null];
        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $P2SH_P2PKH), $testKey, $flags, 0xffffffff, SigHashInterface::ALL, $P2PKH, null];
        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $P2SH_Multisig), $testKey, $flags, 0xffffffff, SigHashInterface::ALL, $multisig, null];
        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $P2WPKH), $testKey, $flags, 0xffffffff, SigHashInterface::ALL, null, null];

        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $P2WSH_P2PK), $testKey, $flags, 0xffffffff, SigHashInterface::ALL, null, $P2PK];
        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $P2WSH_P2PK), $testKey, $flags, 0xffffffff, SigHashInterface::SINGLE, null, $P2PK];
        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $P2WSH_P2PK), $testKey, $flags, 0xffffffff, SigHashInterface::NONE, null, $P2PK];

        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $P2WSH_P2PKH), $testKey, $flags, 0xffffffff, SigHashInterface::ALL, null, $P2PKH];
        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $P2WSH_Multisig), $testKey, $flags, 0xffffffff, SigHashInterface::ALL, null, $multisig];

        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $P2SH_P2WPKH), $testKey, $flags, 0xffffffff, SigHashInterface::ALL, $P2WPKH, null];
        $tests[] = [$ecAdapter, $this->buildCreditingTransaction($ecAdapter, $P2SH_P2WSH), $testKey, $flags, 0xffffffff, SigHashInterface::ALL, $P2WSH_Multisig, $multisig];

        return $tests;
    }

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param TransactionInterface $creditTx
     * @param PrivateKeyInterface $privateKey
     * @param $scriptFlags
     * @param int $nSequence
     * @param int $sigHashType
     * @param ScriptInterface|null $redeemScript
     * @param ScriptInterface|null $witnessScript
     * @dataProvider createTests
     */
    public function testCases(EcAdapterInterface $ecAdapter, TransactionInterface $creditTx, PrivateKeyInterface $privateKey, $scriptFlags, $nSequence = 0xffffffff, $sigHashType = SigHashInterface::ALL, ScriptInterface $redeemScript = null, ScriptInterface $witnessScript = null)
    {
        $utxo = $creditTx->makeUtxo(0);

        $txBuilder = new TxBuilder();
        $txBuilder->spendOutPoint($utxo->getOutPoint(), null, $nSequence);
        $txBuilder->payToAddress(4900000000, $privateKey->getAddress());
        $unsigned = $txBuilder->get();

        $signer = new Signer($unsigned, $ecAdapter);
        $signer->sign(0, $privateKey, $creditTx->getOutput(0), $redeemScript, $witnessScript, $sigHashType);
        $signed = $signer->get();

        $checker = $signed->validator();
        $check = $checker->checkSignatures(ScriptFactory::consensus($scriptFlags), [$creditTx->getOutput(0)]);
        $this->assertTrue($check);
    }
}