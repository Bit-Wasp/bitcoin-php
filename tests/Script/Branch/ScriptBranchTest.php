<?php

namespace BitWasp\Bitcoin\Tests\Script\Branch;

use BitWasp\Bitcoin\Script\Interpreter\Number;
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

    public function getScriptBranchFixtures2()
    {
        $opcodes = new Opcodes();
        $mapOps = $this->calcMapOpNames($opcodes);
        $r1 = "0x14 0xa16ce1cfc1ca2dfc93f8f9aff31f1a4d3ebb96ea";
        $r2 = "0x14 0x376eb254c19e918752f00420da03a45ff6c9c7f6";
        $alice = "0x21 0x0228d9678b4edc130efb1fd3e31fc33004237c53632657ebd18244fe6cf05fb4d3";
        $bob = "0x21 0x02d99b5b59775b152861a62a068896d9eecd550c9acfb18df1cb05cadef26a2b7e";
        $csvTime = Number::int(6000)->getHex();
        $cltvTime = Number::int(6000)->getHex();

        $s1 = "HASH160 DUP {$r1} EQUAL IF {$csvTime} CHECKSEQUENCEVERIFY 2DROP {$alice} ELSE {$r2} EQUAL NOTIF {$cltvTime} CHECKLOCKTIMEVERIFY DROP ENDIF {$bob} ENDIF CHECKSIG";

        $ba1 = [true];
        $bs1 = "HASH160 DUP {$r1} EQUAL IF {$csvTime} CHECKSEQUENCEVERIFY 2DROP {$alice} ELSE NOTIF ENDIF ENDIF CHECKSIG";
        $bmu1 = "HASH160 DUP {$r1} EQUAL {$csvTime} CHECKSEQUENCEVERIFY 2DROP {$alice} CHECKSIG";

        $ba2 = [false, false];
        $bs2 = "HASH160 DUP {$r1} EQUAL IF ELSE {$r2} EQUAL NOTIF {$cltvTime} CHECKLOCKTIMEVERIFY DROP ENDIF {$bob} ENDIF CHECKSIG";
        $bmu2 = "HASH160 DUP {$r1} EQUAL {$r2} EQUAL {$cltvTime} CHECKLOCKTIMEVERIFY DROP {$bob} CHECKSIG";

        $ba3 = [false, true];
        $bs3 = "HASH160 DUP {$r1} EQUAL IF ELSE {$r2} EQUAL NOTIF ENDIF {$bob} ENDIF CHECKSIG";
        $bmu3 = "HASH160 DUP {$r1} EQUAL {$r2} EQUAL NOTIF DROP {$bob} CHECKSIG";

        $sfix = [
            [
                $s1,
                [
                    [$ba1, $bs1, $bmu1],
                    [$ba2, $bs2, $bmu2],
                    [$ba3, $bs3, $bmu3],
                ],
            ],
        ];

        echo json_encode($sfix, JSON_PRETTY_PRINT).PHP_EOL;
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
