<?php

namespace BitWasp\Bitcoin\Tests\Script\Interpreter;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Script\Interpreter\Checker;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

/**
 * This is essentially a port of Bitcoin Core's test suite.
 * When updating:
 *   cp bitcoin/src/test/data/script_tests.json bitcoin-php/tests/Data/script_tests.json
 */
class ScriptTest extends AbstractTestCase
{
    /**
     * @param ScriptInterface $scriptPubKey
     * @param int $amount
     * @return Transaction
     */
    public function buildCreditingTransaction(ScriptInterface $scriptPubKey, $amount = 0)
    {
        return new Transaction(
            1,
            [
                new TransactionInput(
                    new OutPoint(new Buffer("\x00", 32), 0xffffffff),
                    ScriptFactory::sequence([Opcodes::OP_0, Opcodes::OP_0]),
                    TransactionInput::SEQUENCE_FINAL
                )
            ],
            [
                new TransactionOutput($amount, $scriptPubKey)
            ],
            [],
            0
        );
    }

    /**
     * @param TransactionInterface $tx
     * @param ScriptInterface $scriptSig
     * @param ScriptWitnessInterface|null $scriptWitness
     * @return Transaction
     */
    public function buildSpendTransaction(TransactionInterface $tx, ScriptInterface $scriptSig, ScriptWitnessInterface $scriptWitness = null)
    {
        return new Transaction(
            1,
            [
                new TransactionInput(
                    $tx->makeOutPoint(0),
                    $scriptSig,
                    TransactionInput::SEQUENCE_FINAL
                )
            ],
            [
                new TransactionOutput($tx->getOutput(0)->getValue(), new Script())
            ],
            $scriptWitness == null ? [] : [$scriptWitness],
            0
        );
    }

    /**
     * @param string $data
     * @return Script|ScriptInterface
     */
    public function parseTestScript($data)
    {
        if (is_array($data)) {
            return ScriptFactory::sequence($data);
        } else if (is_string($data)) {
            return ScriptFactory::fromHex($data);
        }

        throw new \RuntimeException('Invalid data for test case: supports array (interpreted as sequence), or string (interpreted as hex)');
    }

    /**
     * @param Opcodes $opcodes
     * @return array
     */
    public function calcMapOpNames(Opcodes $opcodes)
    {
        $mapOpNames = [];
        for ($op = 0; $op <= Opcodes::OP_NOP10; $op++) {
            if ($op < Opcodes::OP_NOP && $op != Opcodes::OP_RESERVED) {
                continue;
            }

            $name = $opcodes->getOp($op);
            if ($name === "OP_UNKNOWN") {
                continue;
            }

            $mapOpNames[$name] = $op;
            $mapOpNames[substr($name, 3)] = $op;
        }

        return $mapOpNames;
    }

    /**
     * @param array $mapOpNames
     * @param string $string
     * @return ScriptInterface
     */
    public function calcScriptFromString($mapOpNames, $string)
    {
        $builder = ScriptFactory::create();
        $split = explode(" ", $string);
        foreach ($split as $item) {
            if ($item === 'NOP3') {
                $item = 'OP_CHECKSEQUENCEVERIFY';
            }

            if (strlen($item) == '') {
            } else if (preg_match("/^[0-9]*$/", $item) || substr($item, 0, 1) === "-" && preg_match("/^[0-9]*$/", substr($item, 1))) {
                $builder->int($item);
            } else if (substr($item, 0, 2) === "0x") {
                $scriptConcat = new Script(Buffer::hex(substr($item, 2)));
                $builder->concat($scriptConcat);
            } else if (strlen($item) >= 2 && substr($item, 0, 1) === "'" && substr($item, -1) === "'") {
                $buffer = new Buffer(substr($item, 1, strlen($item) - 2));
                $builder->push($buffer);
            } else if (isset($mapOpNames[$item])) {
                $builder->sequence([$mapOpNames[$item]]);
            } else {
                throw new \RuntimeException('Script parse error: element "' . $item . '"');
            }
        }

        return $builder->getScript();
    }

    /**
     * @return array
     */
    public function prepareTests()
    {
        $ecAdapter = Bitcoin::getEcAdapter();
        $opcodes = new Opcodes();
        $mapOpNames = $this->calcMapOpNames($opcodes);
        $object = json_decode($this->dataFile("script_tests.json"), true);
        $testCount = count($object);
        $vectors = [];
        for ($idx = 0; $idx < $testCount; $idx++) {
            $test = $object[$idx];
            $strTest = end($test);
            $witnessStack = [];
            $amount = 0;
            $pos = 0;
            if (count($test) > 0 && is_array($test[$pos])) {
                for ($i = 0; $i < count($test[$pos]) - 1; $i++) {
                    $witnessStack[] = Buffer::hex($test[$pos][$i]);
                }

                $amt = number_format($test[$pos][$i], 8, '.', '');
                $sat = bcmul($amt, 10**8);
                $amount = $sat;
                $pos++;
            }

            if (count($test) < 4 + $pos) {
                if (count($test) != 1) {
                    throw new \RuntimeException('bad test');
                }

                continue;
            }

            $scriptWitness = new ScriptWitness($witnessStack);
            $scriptSigString = $test[$pos++];
            $scriptSig = $this->calcScriptFromString($mapOpNames, $scriptSigString);

            $scriptPubKeyString = $test[$pos++];
            $scriptPubKey = $this->calcScriptFromString($mapOpNames, $scriptPubKeyString);

            $flags = $this->getScriptFlagsFromString($test[$pos++]);
            $returns = ($test[$pos++]) === 'OK' ? true : false;

            $vectors[] = [$ecAdapter, new Interpreter($ecAdapter), $flags, $returns, $scriptWitness, $scriptSig, $scriptPubKey, $amount, $strTest];
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
     * @dataProvider prepareTests
     */
    public function testScript(EcAdapterInterface $ecAdapter, Interpreter $interpreter, $flags, $expectedResult, ScriptWitnessInterface $scriptWitness, ScriptInterface $scriptSig, ScriptInterface $scriptPubKey, $amount, $strTest)
    {
        $create = $this->buildCreditingTransaction($scriptPubKey, $amount);
        $tx = $this->buildSpendTransaction($create, $scriptSig, $scriptWitness);
        $check = $interpreter->verify($scriptSig, $scriptPubKey, $flags, new Checker($ecAdapter, $tx, 0, $amount), $scriptWitness);

        $this->assertEquals($expectedResult, $check, $strTest);
    }
}
