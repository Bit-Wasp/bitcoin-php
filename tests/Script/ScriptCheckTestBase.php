<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Amount;
use BitWasp\Bitcoin\Script\Consensus\BitcoinConsensus;
use BitWasp\Bitcoin\Script\Consensus\NativeConsensus;
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

abstract class ScriptCheckTestBase extends AbstractTestCase
{
    /**
     * @param ScriptInterface $scriptPubKey
     * @param int $amount
     * @return Transaction
     */
    public function buildCreditingTransaction(ScriptInterface $scriptPubKey, int $amount = 0)
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
     * @return TransactionInterface
     */
    public function buildSpendTransaction(
        TransactionInterface $tx,
        ScriptInterface $scriptSig,
        ScriptWitnessInterface $scriptWitness = null
    ): TransactionInterface {
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
     * @return ScriptInterface
     */
    public function parseTestScript($data): ScriptInterface
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
    public function calcMapOpNames(Opcodes $opcodes): array
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
    public function calcScriptFromString(array $mapOpNames, string $string): ScriptInterface
    {
        $builder = ScriptFactory::create();
        $split = explode(" ", $string);
        foreach ($split as $item) {
            if ($item === 'NOP3') {
                $item = 'OP_CHECKSEQUENCEVERIFY';
            }

            if (strlen($item) == '') {
            } else if (preg_match("/^[0-9]*$/", $item) || substr($item, 0, 1) === "-" && preg_match("/^[0-9]*$/", substr($item, 1))) {
                $builder->int((int) $item);
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
    public function prepareTestData(): array
    {
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
                $sat = bcmul($amt, (string) Amount::COIN);
                $amount = (int) $sat;
                $pos++;
            }

            if (count($test) < 4 + $pos) {
                if (count($test) != 1) {
                    throw new \RuntimeException('bad test');
                }

                continue;
            }

            $scriptWitness = new ScriptWitness(...$witnessStack);
            $scriptSigString = $test[$pos++];
            $scriptSig = $this->calcScriptFromString($mapOpNames, $scriptSigString);

            $scriptPubKeyString = $test[$pos++];
            $scriptPubKey = $this->calcScriptFromString($mapOpNames, $scriptPubKeyString);

            $flags = $this->getScriptFlagsFromString($test[$pos++]);
            $returns = ($test[$pos++]) === 'OK' ? true : false;

            $vectors[] = [$flags, $returns, $scriptWitness, $scriptSig, $scriptPubKey, $amount, $strTest];
        }

        return $vectors;
    }

    /**
     * @param array $ecAdapterFixtures - array<array<EcAdapterInterface>>
     * @return array - array<array<ConsensusInterface,EcAdapterInterface>>
     */
    public function getConsensusAdapters(array $ecAdapterFixtures): array
    {
        $adapters = [];
        foreach ($ecAdapterFixtures as $ecAdapterFixture) {
            list ($ecAdapter) = $ecAdapterFixture;
            $adapters[] = [new NativeConsensus($ecAdapter)];
            if (extension_loaded('bitcoinconsensus')) {
                $adapters[] = [new BitcoinConsensus()];
            }
        }

        return $adapters;
    }
}
