<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction\PSBT;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeySequence;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\SignData;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\PSBT\Creator;
use BitWasp\Bitcoin\Transaction\PSBT\PSBTBip32Derivation;
use BitWasp\Bitcoin\Transaction\PSBT\UpdatableInput;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class UpdatorTest extends AbstractTestCase
{
    public function testUpdateWithScripts()
    {
        $version = 2;
        $locktime = 0;
        $txin1 = new TransactionInput(new OutPoint(Buffer::hex("75ddabb27b8845f5247975c8a5ba7c6f336c4570708ebe230caf6db5217ae858"), 0), new Script());
        $txin2 = new TransactionInput(new OutPoint(Buffer::hex("1dea7cd05979072a3578cab271c02244ea8a090bbb46aa680a65ecd027048d83"), 1), new Script());

        $txOut1 = new TransactionOutput(149990000, ScriptFactory::fromHex("0014d85c2b71d0060b09c9886aeb815e50991dda124d"));
        $txOut2 = new TransactionOutput(100000000, ScriptFactory::fromHex("001400aea9a2e5f0f876a588df5546e8742d1d87008f"));

        $tx = new Transaction($version, [$txin1, $txin2,], [$txOut1, $txOut2], [], $locktime);

        $creator = new Creator();
        $psbt = $creator->createPsbt($tx);
        $this->assertEquals(
            "70736274ff01009a020000000258e87a21b56daf0c23be8e7070456c336f7cbaa5c8757924f545887bb2abdd750000000000ffffffff838d0427d0ec650a68aa46bb0b098aea4422c071b2ca78352a077959d07cea1d0100000000ffffffff0270aaf00800000000160014d85c2b71d0060b09c9886aeb815e50991dda124d00e1f5050000000016001400aea9a2e5f0f876a588df5546e8742d1d87008f000000000000000000",
            $psbt->getBuffer()->getHex()
        );

        $inputTx1 = TransactionFactory::fromHex("0200000000010158e87a21b56daf0c23be8e7070456c336f7cbaa5c8757924f545887bb2abdd7501000000171600145f275f436b09a8cc9a2eb2a2f528485c68a56323feffffff02d8231f1b0100000017a914aed962d6654f9a2b36608eb9d64d2b260db4f1118700c2eb0b0000000017a914b7f5faf40e3d40a5a459b1db3535f2b72fa921e88702483045022100a22edcc6e5bc511af4cc4ae0de0fcd75c7e04d8c1c3a8aa9d820ed4b967384ec02200642963597b9b1bc22c75e9f3e117284a962188bf5e8a74c895089046a20ad770121035509a48eb623e10aace8bfd0212fdb8a8e5af3c94b0b133b95e114cab89e4f7965000000");
        $rsTx1 = new P2shScript(ScriptFactory::fromHex("5221029583bf39ae0a609747ad199addd634fa6108559d6c5cd39b4c2183f1ab96e07f2102dab61ff49a14db6a7d02b0cd1fbb78fc4b18312b5b4e54dae4dba2fbfef536d752ae"));
        $spkTx1 = $rsTx1->getOutputScript();
        $sigData1 = (new SignData())
            ->p2sh($rsTx1);

        $inputTx2 = TransactionFactory::fromHex("0200000001aad73931018bd25f84ae400b68848be09db706eac2ac18298babee71ab656f8b0000000048473044022058f6fc7c6a33e1b31548d481c826c015bd30135aad42cd67790dab66d2ad243b02204a1ced2604c6735b6393e5b41691dd78b00f0c5942fb9f751856faa938157dba01feffffff0280f0fa020000000017a9140fb9463421696b82c833af241c78c17ddbde493487d0f20a270100000017a91429ca74f8a08f81999428185c97b5d852e4063f618765000000");
        $wsTx2 = new P2shScript(ScriptFactory::fromHex("522103089dc10c7ac6db54f91329af617333db388cead0c231f723379d1b99030b02dc21023add904f3d6dcf59ddb906b0dee23529b7ffb9ed50e5e86151926860221f0e7352ae"));
        $rsTx2 = new P2shScript(ScriptFactory::fromHex("00208c2353173743b595dfb4a07b72ba8e42e3797da74e87fe7d9d7497e3b2028903"));
        $sigData2 = (new SignData())
            ->p2sh($rsTx2)
            ->p2wsh($wsTx2);
        $spkTx2 = $rsTx2->getOutputScript();

        $fpr = 0xd90c6a4f;
        $derivs = [];
        $createDeriv = function(string $hexKey, int $fpr, string $path) use (&$derivs) {
            $sequence = new HierarchicalKeySequence();
            $keyFactory = new PublicKeyFactory();
            $key = $keyFactory->fromHex($hexKey);
            $derivs[$key->getPubKeyHash()->getBinary()] = new PSBTBip32Derivation($key->getBuffer(), $fpr, ...$sequence->decodeAbsolute($path)[1]);
        };
        // not all used in script
        $createDeriv("029583bf39ae0a609747ad199addd634fa6108559d6c5cd39b4c2183f1ab96e07f", $fpr, "m/0'/0'/0'");
        $createDeriv("02dab61ff49a14db6a7d02b0cd1fbb78fc4b18312b5b4e54dae4dba2fbfef536d7", $fpr, "m/0'/0'/1'");
        $createDeriv("03089dc10c7ac6db54f91329af617333db388cead0c231f723379d1b99030b02dc", $fpr, "m/0'/0'/2'");
        $createDeriv("023add904f3d6dcf59ddb906b0dee23529b7ffb9ed50e5e86151926860221f0e73", $fpr, "m/0'/0'/3'");
        $createDeriv("03a9a4c37f5996d3aa25dbac6b570af0650394492942460b354753ed9eeca58771", $fpr, "m/0'/0'/4'");
        $createDeriv("027f6399757d2eff55a136ad02c684b1838b6556e5f1b6b34282a94b6b50051096", $fpr, "m/0'/0'/5'");

        $searchDerivs = function (string $pubKey) use ($derivs) {
            if (array_key_exists($pubKey, $derivs)) {
                return $derivs[$pubKey];
            }
            return null;
        };

        $txs = [
            $inputTx1->getTxId()->getBinary() => $inputTx1,
            $inputTx2->getTxId()->getBinary() => $inputTx2,
        ];
        $searchTxs = function (BufferInterface $d) use ($txs) {
            if (array_key_exists($d->getBinary(), $txs)) {
                return $txs[$d->getBinary()];
            }
            return null;
        };

        $scripts = [
            $spkTx1->getBinary() => $sigData1,
            $spkTx2->getBinary() => $sigData2,
        ];

        $searchScripts = function (ScriptInterface $d) use ($scripts) {
            if (array_key_exists($d->getBinary(), $scripts)) {
                return $scripts[$d->getBinary()];
            }
            return null;
        };

        for ($nIn = 0; $nIn < count($psbt->getInputs()); $nIn++) {
            $psbt->updateInput($nIn, function (UpdatableInput $input) use ($psbt, $nIn, $searchTxs, $searchScripts, $searchDerivs): UpdatableInput {
                // setup tx
                $outPoint = $psbt->getTransaction()->getInputs()[$nIn]->getOutPoint();
                $tx = $searchTxs($outPoint->getTxId());
                $this->assertInstanceOf(TransactionInterface::class, $tx);
                /** @var TransactionInterface $tx */
                if ($tx->hasWitness() && !$input->input()->hasWitnessTxOut()) {
                    $input->addWitnessTx($tx);
                } else if (!$input->input()->hasNonWitnessTx()){
                    $input->addNonWitnessTx($tx);
                }

                // setup scripts & derivs from txout
                $txOut = null;
                if ($input->input()->hasWitnessTxOut()) {
                    $txOut = $input->input()->getWitnessTxOut();
                } else if ($input->input()->hasNonWitnessTx()) {
                    $txOut = $input->input()->getNonWitnessTx()->getOutputs()[$outPoint->getVout()];
                }
                $this->assertInstanceOf(TransactionOutputInterface::class, $txOut);

                $scriptCode = $txOut->getScript();
                $signData = $searchScripts($scriptCode);
                $this->assertInstanceOf(SignData::class, $signData);
                /** @var SignData $signData */
                $scriptHash = null;
                if ($scriptCode->isP2SH($scriptHash)) {
                    $scriptCode = $signData->getRedeemScript();
                    $input->addRedeemScript($scriptCode);
                }
                $witnessProgram = null;
                if ($scriptCode->isWitness($witnessProgram)) {
                    $scriptCode = $signData->getWitnessScript();
                    $input->addWitnessScript($scriptCode);
                }

                $classifier = new OutputClassifier();
                $solution = [];
                $derivs = [];
                $type = $classifier->classify($scriptCode, $solution);
                $keyHashes = [];
                switch ($type) {
                    case ScriptType::P2PK:
                        $keyHash = Hash::sha256ripe160($solution);
                        if (($deriv = $searchDerivs($keyHash->getBinary()))) {
                            $derivs[$solution->getBinary()] = $deriv;
                        }
                        break;
                    case ScriptType::P2WKH:
                        if (($deriv = $searchDerivs($solution->getBinary()))) {
                            $derivs[$solution->getBinary()] = $deriv;
                        }
                        break;
                    case ScriptType::P2PKH:
                        if (($deriv = $searchDerivs($solution->getBinary()))) {
                            $derivs[$solution->getBinary()] = $deriv;
                        }
                        break;
                    case ScriptType::MULTISIG:
                        foreach ($solution as $item) {
                            if (($deriv = $searchDerivs($item->getBinary()))) {
                                $derivs[$solution->getBinary()] = $deriv;
                            }
                        }
                        break;
                    default:
                        throw new \RuntimeException("Unexpected script type: $type");
                }

                /** @var PublicKeySerializerInterface $pubKeySerializer */
                $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class);
                foreach ($derivs as $rawKey => $deriv) {
                    $pubKey = $pubKeySerializer->parse($rawKey);
                    $input->addDerivation($pubKey, $deriv);
                }

                return $input;
            });
        }
    }
}
