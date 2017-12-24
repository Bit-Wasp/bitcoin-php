<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script\Interpreter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Script\Interpreter\Checker;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Tests\Script\ScriptCheckTestBase;

/**
 * This is essentially a port of Bitcoin Core's test suite.
 * When updating:
 *   cp bitcoin/src/test/data/script_tests.json bitcoin-php/tests/Data/script_tests.json
 */
class ScriptTest extends ScriptCheckTestBase
{

    /**
     * @return array
     */
    public function prepareInterpreterTests()
    {
        $vectors = [];
        foreach ($this->prepareTestData() as $fixture) {
            list ($flags, $returns, $scriptWitness, $scriptSig, $scriptPubKey, $amount, $strTest) = $fixture;
            foreach ($this->getEcAdapters() as $ecAdapterFixture) {
                list ($ecAdapter) = $ecAdapterFixture;
                $vectors[] = [$ecAdapter, new Interpreter($ecAdapter), $flags, $returns, $scriptWitness, $scriptSig, $scriptPubKey, $amount, $strTest];
            }
        }

        return $vectors;
    }

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param Interpreter $interpreter
     * @param int $flags
     * @param bool $expectedResult
     * @param ScriptWitnessInterface $scriptWitness
     * @param ScriptInterface $scriptSig
     * @param ScriptInterface $scriptPubKey
     * @param int $amount
     * @dataProvider prepareInterpreterTests
     */
    public function testScript(
        EcAdapterInterface $ecAdapter,
        Interpreter $interpreter,
        int $flags,
        bool $expectedResult,
        ScriptWitnessInterface $scriptWitness,
        ScriptInterface $scriptSig,
        ScriptInterface $scriptPubKey,
        int $amount,
        string $strTest
    ) {
        $create = $this->buildCreditingTransaction($scriptPubKey, $amount);
        $tx = $this->buildSpendTransaction($create, $scriptSig, $scriptWitness);
        $check = $interpreter->verify($scriptSig, $scriptPubKey, $flags, new Checker($ecAdapter, $tx, 0, $amount), $scriptWitness);

        $this->assertEquals($expectedResult, $check, $strTest);
    }
}
