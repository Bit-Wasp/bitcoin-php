<?php

namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Factory\InputSigner;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;

class WitnessTxFullTest extends AbstractTestCase
{
    public function getVectors()
    {
        $wif = 'QP3p9tRpTGTefG4a8jKoktSWC7Um8qzvt8wGKMxwWyW3KTNxMxN7';
        $key = PrivateKeyFactory::fromWif($wif);
        $scriptPubKey = ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPublicKey());
        $v0destkey = new WitnessProgram(0, $key->getPubKeyHash());
        $v0destscript = new WitnessProgram(0, Hash::sha256($scriptPubKey->getBuffer()));
        $payToPubkeyScript = ScriptFactory::scriptPubKey()->payToPubKey($key->getPublicKey());
        $multisig = ScriptFactory::scriptPubKey()->multisig(1, [$key->getPublicKey()]);
        $p2shmultisig = ScriptFactory::scriptPubKey()->payToScriptHash($multisig);
        $p2shv0keyhash = new \BitWasp\Bitcoin\Script\P2shScript($v0destkey->getScript());

        $multisig = ScriptFactory::scriptPubKey()->multisig(1, [$key->getPublicKey()]);
        $wp = new \BitWasp\Bitcoin\Script\WitnessProgram(0, \BitWasp\Bitcoin\Crypto\Hash::sha256($multisig->getBuffer()));
        $p2shWitMultisig = ScriptFactory::scriptPubKey()->payToScriptHash($wp->getScript());

        $multisigWpOut = $wp->getScript();

        $witnessv0Keyhash = '01000000000101ad3badb68d43ea31034f182ce88e692e0d4e5d43eca5220c626a229b0d4682330000000000ffffffff011097f305000000001976a914b1ae3ceac136e4bdb733663e7a1e2f0961198a1788ac024730440220578b90b2614136d8c48b87bb9548428f39725313108bd4925feaf0e21e2a1e7a02201ce0349a22d6cdc44f46d1bdd7b4228259840233940755e7030a8932ed56917b012103b848ab6ac853cd69baaa750c70eb352ebeadb07da0ff5bbd642cb285895ee43f00000000';
        $witnessv0ScriptHash = '010000000001019421cafbbc70043c0d511c7edbca8dfb6b56610e0c2363144f3010d5157f19c20000000000ffffffff01e0d5d505000000001976a914b1ae3ceac136e4bdb733663e7a1e2f0961198a1788ac0347304402207d61450ec95da65094a3c15fd341d63fe6771e1fb840ae42a3724fd7342698c3022025af44bfb24206e4fceef599136b2977fbceae92f257158673b5aa25fc500c3f012103b848ab6ac853cd69baaa750c70eb352ebeadb07da0ff5bbd642cb285895ee43f1976a914b1ae3ceac136e4bdb733663e7a1e2f0961198a1788ac00000000';
        $p2shKeyHash = '0100000000010113ae35a2063ba413c3a1bb9b3820c76291e40e83bd3f23c8ff83333f0c64d6230000000017160014b1ae3ceac136e4bdb733663e7a1e2f0961198a17ffffffff01f0b7a205000000001976a914b1ae3ceac136e4bdb733663e7a1e2f0961198a1788ac02473044022051a60cbf963c7f2f957fc0c310b2000462f5af65d5af204f5ba3941b5713d71d022042d19811a450ad3b68ee19441457af54b5d4aa9da5cf7ea6047ec8e9920ab3f2012103b848ab6ac853cd69baaa750c70eb352ebeadb07da0ff5bbd642cb285895ee43f00000000';
        $witMultisigHex = '0100000000010113ae35a2063ba413c3a1bb9b3820c76291e40e83bd3f23c8ff83333f0c64d6230000000000ffffffff0180969800000000001976a914b1ae3ceac136e4bdb733663e7a1e2f0961198a1788ac03004730440220121a629bb5fee3ecaf3e7a0b111101c51de816f427eaedd992b57f49b69b228e0220402ecd144a7321b4bad6ba3bfa5876b755b9c52a8c8ab17a33830d5929a76cbe0125512103b848ab6ac853cd69baaa750c70eb352ebeadb07da0ff5bbd642cb285895ee43f51ae00000000';

        $utxo1 = new Utxo(
            new OutPoint(Buffer::hex('3382460d9b226a620c22a5ec435d4e0d2e698ee82c184f0331ea438db6ad3bad'), 0),
            new TransactionOutput(99900000, $v0destkey->getScript())
        );

        $utxo2 = new Utxo(
            new OutPoint(Buffer::hex('c2197f15d510304f1463230c0e61566bfb8dcadb7e1c510d3c0470bcfbca2194'), 0),
            new TransactionOutput(99990000, $v0destscript->getScript())
        );

        $utxo3 = new Utxo(
            new OutPoint(Buffer::hex('23d6640c3f3383ffc8233fbd830ee49162c720389bbba1c313a43b06a235ae13'), 0),
            new TransactionOutput(95590000, $p2shv0keyhash->getOutputScript())
        );
        $utxo4 = new Utxo(
            new OutPoint(Buffer::hex('23d6640c3f3383ffc8233fbd830ee49162c720389bbba1c313a43b06a235ae13'), 0),
            new TransactionOutput(10000000, $payToPubkeyScript)
        );
        $pubkeyHex = '0100000000010113ae35a2063ba413c3a1bb9b3820c76291e40e83bd3f23c8ff83333f0c64d62300000000484730440220423ba0d56785c19f621003d243493926f747345b09d5d09b52f4843b6fdaab16022045c86d35dd2791507152a423bacfbec2cf9ddfaee14c413564d6a05eb1c55b7e01ffffffff01f0b9f505000000001976a914b1ae3ceac136e4bdb733663e7a1e2f0961198a1788ac0000000000';

        $utxo5 = new Utxo(
            new OutPoint(Buffer::hex('23d6640c3f3383ffc8233fbd830ee49162c720389bbba1c313a43b06a235ae13'), 0),
            new TransactionOutput(10000000, $scriptPubKey)
        );
        $pubKeyHashHex = '0100000000010113ae35a2063ba413c3a1bb9b3820c76291e40e83bd3f23c8ff83333f0c64d623000000006a47304402203f965bdf792ea80c1f96e4292d1edb52ca62f22c7511aefca967fb9f3067063402204acbfaa4e7f1d5631227d491426d89d954d5e2abfed6f0dbc300216f01916baa012103b848ab6ac853cd69baaa750c70eb352ebeadb07da0ff5bbd642cb285895ee43fffffffff0100000000000000001976a914b1ae3ceac136e4bdb733663e7a1e2f0961198a1788ac0000000000';

        $utxo6 = new Utxo(
            new OutPoint(Buffer::hex('23d6640c3f3383ffc8233fbd830ee49162c720389bbba1c313a43b06a235ae13'), 0),
            new TransactionOutput(10000000, $multisig)
        );
        $multisigHex = '0100000000010113ae35a2063ba413c3a1bb9b3820c76291e40e83bd3f23c8ff83333f0c64d623000000004a00483045022100e332e8367d5fee22c205ce1bf4e01e39f1a8decb3ba20d1336770cf38b8ee72d022076b5f83b3ee15390133b7ebf526ec189eb73cc6ee0a726f70b939bc51fa18d8001ffffffff0180969800000000001976a914b1ae3ceac136e4bdb733663e7a1e2f0961198a1788ac0000000000';

        $utxo7 = new Utxo(
            new OutPoint(Buffer::hex('23d6640c3f3383ffc8233fbd830ee49162c720389bbba1c313a43b06a235ae13'), 0),
            new TransactionOutput(10000000, $p2shmultisig)
        );
        $p2shmultisigHex = '0100000000010113ae35a2063ba413c3a1bb9b3820c76291e40e83bd3f23c8ff83333f0c64d623000000007000483045022100e332e8367d5fee22c205ce1bf4e01e39f1a8decb3ba20d1336770cf38b8ee72d022076b5f83b3ee15390133b7ebf526ec189eb73cc6ee0a726f70b939bc51fa18d800125512103b848ab6ac853cd69baaa750c70eb352ebeadb07da0ff5bbd642cb285895ee43f51aeffffffff0180969800000000001976a914b1ae3ceac136e4bdb733663e7a1e2f0961198a1788ac0000000000';

        $utxo8 = new Utxo(
            new OutPoint(Buffer::hex('23d6640c3f3383ffc8233fbd830ee49162c720389bbba1c313a43b06a235ae13'), 0),
            new TransactionOutput(10000000, $p2shWitMultisig)
        );
        $p2shWitMultisigHex = '0100000000010113ae35a2063ba413c3a1bb9b3820c76291e40e83bd3f23c8ff83333f0c64d623000000002322002086b2dcecbf2e0f0e4095ef11bc8834e2e148d245f844f0b8091389fef91b69ffffffffff0180969800000000001976a914b1ae3ceac136e4bdb733663e7a1e2f0961198a1788ac03004730440220121a629bb5fee3ecaf3e7a0b111101c51de816f427eaedd992b57f49b69b228e0220402ecd144a7321b4bad6ba3bfa5876b755b9c52a8c8ab17a33830d5929a76cbe0125512103b848ab6ac853cd69baaa750c70eb352ebeadb07da0ff5bbd642cb285895ee43f51ae00000000';

        $utxo9 = new Utxo(
            new OutPoint(Buffer::hex('23d6640c3f3383ffc8233fbd830ee49162c720389bbba1c313a43b06a235ae13'), 0),
            new TransactionOutput(10000000, $multisigWpOut)
        );

        $ecAdapter = Bitcoin::getEcAdapter();
        $vectors = [
            // key, fund.tx, fund.vout, fund.scriptPubKey, fund.value, spendoutvalue, expected tx hex
            [$ecAdapter, $key, $utxo1, 99850000, $witnessv0Keyhash,    null,  null],
            [$ecAdapter, $key, $utxo2, 97900000, $witnessv0ScriptHash, null,  $scriptPubKey],
            [$ecAdapter, $key, $utxo3, 94550000, $p2shKeyHash,         $p2shv0keyhash, null],
            [$ecAdapter, $key, $utxo4, 99990000, $pubkeyHex,           null, null],
            [$ecAdapter, $key, $utxo5, 0,        $pubKeyHashHex,       null, null],
            [$ecAdapter, $key, $utxo6, 10000000, $multisigHex,         null, null],
            [$ecAdapter, $key, $utxo7, 10000000, $p2shmultisigHex,     $multisig, null],
            [$ecAdapter, $key, $utxo8, 10000000, $p2shWitMultisigHex,  $wp->getScript(), $multisig],
            [$ecAdapter, $key, $utxo9, 10000000, $witMultisigHex,      null, $multisig],
        ];

        return $vectors;
    }

    /**
     * @param EcAdapterInterface $ec
     * @param PrivateKeyInterface $key
     * @param Utxo $utxo
     * @param int $spendAmount
     * @param int $expectedTx
     * @param ScriptInterface|null $redeemScript
     * @param ScriptInterface|null $witnessScript
     * @dataProvider getVectors
     */
    public function testWitnessSignAndVerify(EcAdapterInterface $ec, PrivateKeyInterface $key, Utxo $utxo, $spendAmount, $expectedTx, ScriptInterface $redeemScript = null, ScriptInterface $witnessScript = null)
    {
        // Build unsigned transaction
        $tx = TransactionFactory::build()
            ->spendOutPoint($utxo->getOutPoint())
            ->payToAddress($spendAmount, $key->getPublicKey()->getAddress())
            ->get();

        $signed = (new Signer($tx, $ec))
            ->sign(0, $key, $utxo->getOutput(), $redeemScript, $witnessScript)
            ->get();

        $consensus = ScriptFactory::consensus(InterpreterInterface::VERIFY_P2SH | InterpreterInterface::VERIFY_WITNESS);

        $check = $signed->validator()->checkSignature($consensus, 0, $utxo->getOutput());
        $this->assertTrue($check);
        $this->assertEquals($expectedTx, $signed->getWitnessBuffer()->getHex());

        $signer = new InputSigner($ec, $signed, 0, $utxo->getOutput());
        $this->assertTrue($signer->isFullySigned());
    }
}
