<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterpreter;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Script\ScriptInterpreterFlags;

class ScriptInterpreterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $flagStr
     * @return ScriptInterpreterFlags
     */
    private function setFlags($flagStr)
    {
        $array = explode(",", $flagStr);
        $flags = new ScriptInterpreterFlags();
        foreach ($array as $activeFlag) {
            $flags->$activeFlag = true;
        }
        return $flags;
    }

    public function testScript()
    {
        $f = file_get_contents(__DIR__ . '/../Data/scriptinterpreter.simple.json');
        $json = json_decode($f);

        // Pass a dummy transaction since not testing OP_CHECKSIG

        foreach ($json->test as $c => $test) {
            $flags = $this->setFlags($test->flags);
            $i = new ScriptInterpreter(Bitcoin::getEcAdapter(), new Transaction(), $flags);
            $scriptSig = ScriptFactory::fromHex($test->scriptSig);
            $scriptPubKey = ScriptFactory::fromHex($test->scriptPubKey);

            $i->setScript($scriptSig)->run();
            $testResult = $i->setScript($scriptPubKey)->run();

            $this->assertTrue($testResult, $test->result);
        }
    }
}
