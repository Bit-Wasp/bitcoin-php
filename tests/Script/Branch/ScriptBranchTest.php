<?php

namespace BitWasp\Bitcoin\Tests\Script\Branch;

use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Path\BranchInterpreter;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class ScriptBranchTest extends AbstractTestCase
{

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

    public function getScriptBranchFixtures3()
    {
        $opcodes = new Opcodes();
        $mapOps = $this->calcMapOpNames($opcodes);

        $file = $this->dataFile("branch_test.json");
        $sfix = json_decode($file, true)['vectors'];

        foreach ($sfix as &$fixture) {
            $fixture[0]= $this->calcScriptFromString($mapOps, $fixture[0]);
            foreach ($fixture[1] as &$record) {
                $record[1] = $this->calcScriptFromString($mapOps, $record[1]);
                $record[2] = $this->calcScriptFromString($mapOps, $record[2]);
            }
        }

        return $sfix;
    }

    /**
     * @param ScriptInterface $script
     * @param array $fixtureData
     * @dataProvider getScriptBranchFixtures3
     */
    public function testBranchTest(ScriptInterface $script, array $fixtureData)
    {
        $bi = new BranchInterpreter();
        $branches = $bi->getScriptBranches($script);

        $this->assertEquals(count($fixtureData), count($branches));
        foreach ($fixtureData as $fixture) {
            /**
             * @var ScriptInterface $expectedBranch
             */
            list ($vfInput, $expectedBranch) = $fixture;

            $foundBranch = false;
            $ub = null;
            foreach ($branches as $branch) {
                if ($branch->getBranchDescriptor() === $vfInput) {
                    $foundBranch = true;
                    $this->assertTrue($expectedBranch->equals($branch->getNeuteredScript()));
                }
            }

            $this->assertTrue($foundBranch);
        }
    }
}
