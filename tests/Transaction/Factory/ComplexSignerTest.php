<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction\Factory;

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\Multisig;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkey;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkeyHash;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Factory\Checksig;
use BitWasp\Bitcoin\Transaction\Factory\Conditional;
use BitWasp\Bitcoin\Transaction\Factory\SignData;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Buffertools\Buffer;

class ComplexSignerTest extends AbstractTestCase
{

    /**
     * @var PrivateKeyInterface[]
     */
    protected $privateKeys = [];

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->initKeyStore();
    }

    protected function initKeyStore()
    {
        $factory = new PrivateKeyFactory(true);
        $this->privateKeys[] = $factory->fromHex("990000009900000099000000990000009900000099000000ff00000099000000");
        $this->privateKeys[] = $factory->fromHex("98aa0000990000009900000099000000990000009900000099000ff099000000");
        $this->privateKeys[] = $factory->fromHex("98bb000099000000990ff0009900000099000000990000009900000099000000");
        $this->privateKeys[] = $factory->fromHex("98cc00009900000099000000990000009900ff00990000009900000099000000");
        $this->privateKeys[] = $factory->fromHex("98cc0000990ed00099000000990920009900ff009900000099000000990000cc");
    }

    /**
     * @param $idx
     * @return PrivateKeyInterface
     */
    protected function getKeyFromStore(int $idx)
    {
        if (!array_key_exists($idx, $this->privateKeys)) {
            throw new \RuntimeException("Key at {$idx} is missing");
        }

        return $this->privateKeys[$idx];
    }

    /**
     * NOTIF [AliceKey] CHECKSIGVERIFY ENDIF [BobKey] CHECKSIG
     * @return array
     */
    private function conditionalBlockWithMandatoryEnding()
    {
        $pA = $this->getKeyFromStore(0);
        $pB = $this->getKeyFromStore(1);

        $pkA = $pA->getPublicKey();
        $pkB = $pB->getPublicKey();

        $script_1 = ScriptFactory::sequence([
            Opcodes::OP_NOTIF,
            $pkA->getBuffer(), Opcodes::OP_CHECKSIGVERIFY,
            Opcodes::OP_ENDIF,
            $pkB->getBuffer(), Opcodes::OP_CHECKSIG,
        ]);

        $paths_1 = [
            [true],
            [false],
        ];

        $keys_1 = [
            [
                [],
                [$pB],
            ],
            [
                [],
                [$pA],
                [$pB],
            ],
        ];

        return [$script_1, $paths_1, $keys_1,];
    }

    /**
     * 2-of-2 MULTISIG IF [Alice] CHECKSIG ELSE [BobKey] CHECKSIG ENDIF
     * @return array
     */
    private function mandatoryStartWithConditionalEnding()
    {
        $pA = $this->getKeyFromStore(0);
        $pB1 = $this->getKeyFromStore(1);
        $pB2 = $this->getKeyFromStore(2);
        $pC = $this->getKeyFromStore(3);

        $pkA = $pA->getPublicKey();
        $pkB1 = $pB1->getPublicKey();
        $pkB2 = $pB2->getPublicKey();
        $pkC = $pC->getPublicKey();

        return [
            ScriptFactory::sequence([
                Opcodes::OP_2, $pkB1->getBuffer(), $pkB2->getBuffer(), Opcodes::OP_2, Opcodes::OP_CHECKMULTISIG,
                Opcodes::OP_IF,
                $pkA->getBuffer(), Opcodes::OP_CHECKSIG,
                Opcodes::OP_ELSE,
                $pkC->getBuffer(), Opcodes::OP_CHECKSIG,
                Opcodes::OP_ENDIF,
            ]),
            [
                [true],
                [false],
            ],
            [
                [
                    [$pB1, $pB2],
                    [],
                    [$pA]
                ],
                [
                    [],
                    [],
                    [$pC],
                ],
            ],
        ];
    }

    /**
     * IF [Alice] CHECKSIG ELSE [Bob] CHECKSIG
     * @return array
     */
    private function similarConditionalSection()
    {
        $pB = $this->getKeyFromStore(0);
        $pC = $this->getKeyFromStore(1);

        $pkB = $pB->getPublicKey();
        $pkC = $pC->getPublicKey();

        return [
            ScriptFactory::sequence([
                Opcodes::OP_IF,
                $pkB->getBuffer(), Opcodes::OP_CHECKSIG,
                Opcodes::OP_ELSE,
                $pkC->getBuffer(), Opcodes::OP_CHECKSIG,
                Opcodes::OP_ENDIF,
            ]),
            [
                [true],
                [false],
            ],
            [
                [
                    [],
                    [$pB]
                ],

                [
                    [],
                    [$pC],
                ],
            ],
        ];
    }

    /**
     * IF 2 of 2 MULTISIG ELSE [Alice] CHECKSIG ENDIF
     * @return array
     */
    private function differentlyTypedConditionalSection()
    {
        $pA = $this->getKeyFromStore(0);
        $pB1 = $this->getKeyFromStore(1);
        $pB2 = $this->getKeyFromStore(2);

        $pkA = $pA->getPublicKey();
        $pkB1 = $pB1->getPublicKey();
        $pkB2 = $pB2->getPublicKey();

        return [
            ScriptFactory::sequence([
                Opcodes::OP_IF,
                Opcodes::OP_2, $pkB1->getBuffer(), $pkB2->getBuffer(), Opcodes::OP_2, Opcodes::OP_CHECKMULTISIG,
                Opcodes::OP_ELSE,
                $pkA->getBuffer(), Opcodes::OP_CHECKSIG,
                Opcodes::OP_ENDIF,
            ]),
            [
                [true],
                [false],
            ],
            [
                [
                    [],
                    [$pB1, $pB2]
                ],
                [
                    [],
                    [$pA]
                ],
            ],
        ];
    }

    /**
     * IF 2 of 2 MULTISIG ELSE [Alice] CHECKSIG ENDIF
     * @return array
     */
    private function oneNestedNotif()
    {
        $pA = $this->getKeyFromStore(0);
        $pB = $this->getKeyFromStore(1);
        $pC = $this->getKeyFromStore(2);
        $pD = $this->getKeyFromStore(3);

        $pkA = $pA->getPublicKey();
        $pkB = $pB->getPublicKey();
        $pkC = $pC->getPublicKey();
        $pkD = $pD->getPublicKey();

        return [
            ScriptFactory::sequence([
                Opcodes::OP_IF,
                $pkA->getBuffer(), Opcodes::OP_CHECKSIG,
                Opcodes::OP_ELSE,
                $pkB->getBuffer(), Opcodes::OP_CHECKSIG,
                Opcodes::OP_NOTIF,
                $pkC->getBuffer(), Opcodes::OP_CHECKSIG,
                Opcodes::OP_ELSE,
                $pkD->getBuffer(), Opcodes::OP_CHECKSIG,
                Opcodes::OP_ENDIF,
                Opcodes::OP_ENDIF,
            ]),
            [
                [true],
                [false, true],
                [false, false],
            ],
            [
                [
                    [],
                    [$pA]
                ],
                [
                    [],
                    [$pB],
                    [],
                    [$pD],
                ],
                [
                    [],
                    [],
                    [],
                    [$pC],
                ],
            ],
        ];
    }

    /**
     * [Alice] CHECKSIG
     * @return array
     */
    private function simpleStillWorks()
    {
        $pB = $this->getKeyFromStore(0);

        $pkB = $pB->getPublicKey();

        return [
            ScriptFactory::sequence([
                $pkB->getBuffer(), Opcodes::OP_CHECKSIG,
            ]),
            [
                [],
            ],
            [
                [
                    [$pB]
                ],
            ],
        ];
    }

    /**
     * [Alice] CHECKSIGVERIFY [Bob] CHECKSIG
     * @return array
     */
    private function twoMildlySimilarTemplates()
    {
        $pA = $this->getKeyFromStore(0);
        $pB = $this->getKeyFromStore(1);

        $pkA = $pA->getPublicKey();
        $pkB = $pB->getPublicKey();

        return [
            ScriptFactory::sequence([
                $pkA->getBuffer(), Opcodes::OP_CHECKSIGVERIFY,
                $pkB->getBuffer(), Opcodes::OP_CHECKSIG,
            ]),
            [
                [],
            ],
            [
                [
                    [$pA],
                    [$pB],
                ],
            ],
        ];
    }

    /**
     * 2of3 CHECKMULTISIGVERIFY [Bob] CHECKSIG
     * @return array
     */
    private function twoRatherDifferentTemplates()
    {
        $pA = $this->getKeyFromStore(0);
        $pB = $this->getKeyFromStore(1);
        $pC = $this->getKeyFromStore(2);
        $pD = $this->getKeyFromStore(3);

        $pkA = $pA->getPublicKey();
        $pkB = $pB->getPublicKey();
        $pkC = $pC->getPublicKey();
        $pkD = $pD->getPublicKey();

        return [
            ScriptFactory::sequence([
                Opcodes::OP_2, $pkA->getBuffer(), $pkB->getBuffer(), $pkC->getBuffer(), Opcodes::OP_3, Opcodes::OP_CHECKMULTISIGVERIFY,
                $pkD->getBuffer(), Opcodes::OP_CHECKSIG,
            ]),
            [
                [],
                [],
                [],
            ],
            [
                [
                    [$pA, $pB],
                    [$pD],
                ],
                [
                    [$pA, $pC],
                    [$pD],
                ],
                [
                    [$pB, $pC],
                    [$pD],
                ],
            ],
        ];
    }

    /**
     * 2of3 CHECKMULTISIGVERIFY [Bob] CHECKSIG
     * @return array
     */
    private function lotsOfTemplates()
    {
        $pA = $this->getKeyFromStore(0);
        $pB = $this->getKeyFromStore(1);
        $pC = $this->getKeyFromStore(2);
        $pD = $this->getKeyFromStore(3);

        $pkA = $pA->getPublicKey();
        $pkB = $pB->getPublicKey();
        $pkC = $pC->getPublicKey();
        $pkD = $pD->getPublicKey();

        return [
            ScriptFactory::sequence([
                Opcodes::OP_1, $pkA->getBuffer(), $pkB->getBuffer(), Opcodes::OP_2, Opcodes::OP_CHECKMULTISIGVERIFY,
                $pkC->getBuffer(), Opcodes::OP_CHECKSIGVERIFY,
                Opcodes::OP_DUP, Opcodes::OP_HASH160, $pkD->getPubKeyHash(), Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG,
            ]),
            [
                [],
                [],
            ],
            [
                [
                    [$pA],
                    [$pC],
                    [$pD],
                ],
                [
                    [$pB],
                    [$pC],
                    [$pD],
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    public function complexScriptProvider()
    {
        return [
            $this->conditionalBlockWithMandatoryEnding(),
            $this->mandatoryStartWithConditionalEnding(),
            $this->similarConditionalSection(),
            $this->differentlyTypedConditionalSection(),
            $this->oneNestedNotif(),
            $this->simpleStillWorks(),
            $this->twoMildlySimilarTemplates(),
            $this->twoRatherDifferentTemplates(),
            $this->lotsOfTemplates(),
        ];
    }

    public function complexTestProvider()
    {
        $addrCreator = new AddressCreator();
        $spend = (new TxBuilder())
            ->spendOutPoint(new OutPoint(new Buffer('abcd', 32), 0))
            ->payToAddress(10000000, $addrCreator->fromString('1BQLNJtMDKmMZ4PyqVFfRuBNvoGhjigBKF'))
            ->get();

        $fixtures = [];
        foreach ($this->complexScriptProvider() as $fixture) {

            /**
             * @var ScriptInterface $script
             * @var array $vPaths
             * @var array $vPathStepKeys
             */
            list ($script, $vPaths, $vPathStepKeys) = $fixture;

            if (count($vPaths) != count($vPathStepKeys)) {
                throw new \RuntimeException("Invalid data provider");
            }

            $n = count($vPaths);
            for ($i = 0; $i < $n; $i++) {
                $fixtures[] = [
                    $spend,
                    new TransactionOutput(100000000, $script),
                    $vPaths[$i],
                    $vPathStepKeys[$i],
                ];
            }
        }

        return $fixtures;
    }

    /**
     * @param TransactionInterface $unsigned
     * @param TransactionOutputInterface $txOut
     * @param array $branch
     * @param array $branchKeyList
     * @dataProvider complexTestProvider
     */
    public function testCase(TransactionInterface $unsigned, TransactionOutputInterface $txOut, array $branch, array $branchKeyList)
    {
        $signer = new Signer($unsigned);
        $signer->allowComplexScripts(true);

        $signData = new SignData();
        $signData->logicalPath($branch);

        $input = $signer->input(0, $txOut, $signData);

        foreach ($branchKeyList as $i => $stepKeys) {
            $step = $input->step($i);

            if (count($stepKeys) > 0) {
                $this->assertInstanceOf(Checksig::class, $step);

                foreach ($stepKeys as $key) {
                    $input->signStep($i, $key);
                }
            }
        }

        $flags = Interpreter::VERIFY_WITNESS | Interpreter::VERIFY_P2SH | Interpreter::VERIFY_DERSIG;
        $result = $input->verify($flags);
        $this->assertTrue($result, "script should verify");

        $complete = $signer->get();
        $signed = new Signer($complete);
        $signed->allowComplexScripts(true);

        $sinput = $signed->input(0, $txOut, $signData);

        foreach ($branchKeyList as $i => $stepKeys) {
            $step = $input->step($i);
            $sstep = $sinput->step($i);

            if ($step instanceof Checksig) {
                $this->assertInstanceOf(Checksig::class, $sstep);
                /** @var Checksig $sstep */
                $this->assertEquals($step->getType(), $sstep->getType());
                $this->assertEquals($step->getInfo()->getType(), $sstep->getInfo()->getType());

                $this->assertEquals(get_class($step->getInfo()), get_class($sstep->getInfo()));
                $info = $step->getInfo();
                if ($info instanceof Multisig) {
                    $other = $sstep->getInfo();
                    $this->assertEquals($info->isChecksigVerify(), $other->isChecksigVerify());
                    $this->assertEquals($info->getKeyCount(), $other->getKeyCount());
                    $this->assertEquals($info->isChecksigVerify(), $other->isChecksigVerify());

                    for ($i = 0, $keyCount = $info->getKeyCount(); $i < $keyCount; $i++) {
                        $this->assertTrue($info->getKeyBuffers()[$i]->equals($other->getKeyBuffers()[$i]));
                    }
                } else if ($info instanceof PayToPubkey) {
                    $other = $sstep->getInfo();
                    $this->assertEquals($info->isChecksigVerify(), $other->isChecksigVerify());
                    $this->assertTrue($info->getKeyBuffer()->equals($other->getKeyBuffer()));
                } else if ($info instanceof PayToPubkeyHash) {
                    $other = $sstep->getInfo();
                    $this->assertEquals($info->isChecksigVerify(), $other->isChecksigVerify());
                    $this->assertTrue($info->getPubKeyHash()->equals($other->getPubKeyHash()));
                }
            } else if ($step instanceof Conditional || $sstep instanceof Conditional) {
                /** @var Conditional $sstep */
                $this->assertInstanceOf(Conditional::class, $sstep);
                $this->assertInstanceOf(Conditional::class, $step);
                $this->assertEquals($step->hasValue(), $sstep->hasValue());
                if ($step->hasValue()) {
                    $this->assertEquals($step->getValue(), $sstep->getValue());
                }
            }

            if (count($stepKeys) > 0) {
                $this->assertInstanceOf(Checksig::class, $sstep);
                $this->assertInstanceOf(Checksig::class, $step);

                /**
                 * @var Checksig $step
                 * @var Checksig $sstep
                 */

                $this->assertEquals($step->getRequiredSigs(), $sstep->getRequiredSigs(), "`requiredSigs` should match after extracting signatures");
                $this->assertEquals(count($step->getSignatures()), count($sstep->getSignatures()), "number of signatures should match after extracting signatures");
                $this->assertEquals($step->isFullySigned(), $sstep->isFullySigned(), "isFullySigned should match after extracting signatures");

                for ($i = 0; $i < count($step->getKeys()); $i++) {
                    $this->assertEquals($step->hasKey($i), $sstep->hasKey($i));

                    if ($step->hasKey($i)) {
                        $this->assertTrue($step->getKey($i)->equals($sstep->getKey($i)));
                    }

                    $this->assertEquals($step->hasSignature($i), $sstep->hasSignature($i));
                    if ($step->hasSignature($i)) {
                        $this->assertTrue($step->getSignature($i)->equals($sstep->getSignature($i)));
                    }
                }
            }
        }
    }
}
