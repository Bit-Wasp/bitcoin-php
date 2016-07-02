<?php

namespace BitWasp\Bitcoin\Tests\Script\Interpreter;

use BitWasp\Bitcoin\Amount;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Collection\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Collection\Transaction\TransactionOutputCollection;
use BitWasp\Bitcoin\Collection\Transaction\TransactionWitnessCollection;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
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

class ScriptTest extends AbstractTestCase
{
    public function buildCreditingTransaction(ScriptInterface $scriptPubKey, $amount = 0)
    {
        return new Transaction(
            1,
            new TransactionInputCollection([
                new TransactionInput(
                    new OutPoint(new Buffer("\x00", 32), 0xffffffff),
                    ScriptFactory::sequence([Opcodes::OP_0, Opcodes::OP_0]),
                    TransactionInput::SEQUENCE_FINAL
                )
            ]),
            new TransactionOutputCollection([
                new TransactionOutput($amount, $scriptPubKey)
            ]),
            null,
            0
        );
    }

    public function buildSpendTransaction(TransactionInterface $tx, ScriptInterface $scriptSig, ScriptWitnessInterface $scriptWitness = null)
    {
        return new Transaction(
            1,
            new TransactionInputCollection([
                new TransactionInput(
                    $tx->makeOutPoint(0),
                    $scriptSig,
                    TransactionInput::SEQUENCE_FINAL
                )
            ]),
            new TransactionOutputCollection([
                new TransactionOutput($tx->getOutput(0)->getValue(), new Script())
            ]),
            new TransactionWitnessCollection($scriptWitness == null ? [] : [$scriptWitness]),
            0
        );
    }

    public function parseTestScript($data)
    {
        if (is_array($data)) {
            return ScriptFactory::sequence($data);
        } else if (is_string($data)) {
            return ScriptFactory::fromHex($data);
        }

        throw new \RuntimeException('Invalid data for test case: supports array (interpreted as sequence), or string (interpreted as hex)');
    }

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

    public function calcScriptFromString($mapOpNames, $string)
    {
        $builder = ScriptFactory::create();
        $split = explode(" ", $string);
        foreach ($split as $item) {
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

    public function calcMapFlagNames()
    {
        return [
            "NONE" => Interpreter::VERIFY_NONE,
            "P2SH" => Interpreter::VERIFY_P2SH,
            "STRICTENC" => Interpreter::VERIFY_STRICTENC,
            "DERSIG" => Interpreter::VERIFY_DERSIG,
            "LOW_S" => Interpreter::VERIFY_LOW_S,
            "SIGPUSHONLY" => Interpreter::VERIFY_SIGPUSHONLY,
            "MINIMALDATA" => Interpreter::VERIFY_MINIMALDATA,
            "NULLDUMMY" => Interpreter::VERIFY_NULL_DUMMY,
            "DISCOURAGE_UPGRADABLE_NOPS" => Interpreter::VERIFY_DISCOURAGE_UPGRADABLE_NOPS,
            "CLEANSTACK" => Interpreter::VERIFY_CLEAN_STACK,
            "CHECKLOCKTIMEVERIFY" => Interpreter::VERIFY_CHECKLOCKTIMEVERIFY,
            "CHECKSEQUENCEVERIFY" => Interpreter::VERIFY_CHECKSEQUENCEVERIFY,
            "WITNESS" => Interpreter::VERIFY_WITNESS,
            "DISCOURAGE_UPGRADABLE_WITNESS_PROGRAM" => Interpreter::VERIFY_DISCOURAGE_UPGRADABLE_WITNESS_PROGRAM,
        ];
    }

    public function calcScriptFlags(array $mapFlagNames, $string)
    {
        if (strlen($string) === 0) {
            return Interpreter::VERIFY_NONE;
        }

        $flags = 0;
        $words = explode(",", $string);
        foreach ($words as $word) {
            if (!isset($mapFlagNames[$word])) {
                throw new \RuntimeException('Unknown verification flag: ' . $word);
            }

            $flags |= $mapFlagNames[$word];
        }

        return $flags;
    }

    public function prepareTests()
    {
        $ecAdapter = Bitcoin::getEcAdapter();
        $opcodes = new Opcodes();
        $mapOpNames = $this->calcMapOpNames($opcodes);
        $mapFlagNames = $this->calcMapFlagNames();
        $object = json_decode(file_get_contents(__DIR__."/../../Data/script_tests.json"), true);
        $testCount = count($object);
        $vectors = [];
        $phpecc = new EcAdapter(Bitcoin::getMath(), Bitcoin::getGenerator());
        $calcAm = new Amount();
        //$testCount = 1100 ;
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

            $flags = $this->calcScriptFlags($mapFlagNames, $test[$pos++]);
            $returns = ($test[$pos++]) === 'OK' ? true : false;

            if ($ecAdapter instanceof \BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter) {
                //if ($flags & Interpreter::VERIFY_DERSIG) {
                 //   $case = [$ecAdapter, new Interpreter($ecAdapter), $flags, $returns, $scriptWitness, $scriptSig, $scriptPubKey, $amount, $strTest];
               // } else {
                    $case = [$phpecc, new Interpreter($phpecc), $flags, $returns, $scriptWitness, $scriptSig, $scriptPubKey, $amount, $strTest];
                //}
            } else {
                $case = [$ecAdapter, new Interpreter($ecAdapter), $flags, $returns, $scriptWitness, $scriptSig, $scriptPubKey, $amount, $strTest];
            }

            $vectors[] = $case;
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
        if ($check == $expectedResult) {
            ob_clean();
        }
        
        $this->assertEquals($expectedResult, $check, $strTest);
    }
}
