<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptInterpreter;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Script\ScriptInterpreterFlags;
use BitWasp\Buffertools\Buffer;

class ScriptInterpreterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $flagStr
     * @return ScriptInterpreterFlags
     */
    private function setFlags($flagStr)
    {
        $array = explode(",", $flagStr);
        $int = 0;
        $checkdisabled = false;
        foreach ($array as $activeFlag) {
            if ($activeFlag == 'checkDisabledOpcodes') {
                $checkdisabled = true;
                continue;
            }

            $f = constant('\BitWasp\Bitcoin\Script\ScriptInterpreterFlags::'.$activeFlag);
            $int |= $f;
        }

        return new ScriptInterpreterFlags($int, $checkdisabled);
    }

    public function testS()
    {
        $ec = Bitcoin::getEcAdapter();

        $hex = '01010101';
        $pubHex = '9c010188';
        $scriptSig = new Script(Buffer::hex($hex));
        $scriptPubKey = new Script(Buffer::hex($pubHex));

        $f = new ScriptInterpreterFlags(0);
        $i = new ScriptInterpreter($ec, new Transaction(), $f);

        $i->setScript($scriptSig)->run();
        $r = $i->setScript($scriptPubKey)->run();

        $this->assertTrue($r);

    }

    public function getScripts()
    {
        $f = file_get_contents(__DIR__ . '/../Data/scriptinterpreter.simple.json');
        $json = json_decode($f);

        $vectors = [];
        foreach ($json->test as $c => $test) {
            $flags = $this->setFlags($test->flags);
            $scriptSig = ScriptFactory::fromHex($test->scriptSig);
            $scriptPubKey = ScriptFactory::fromHex($test->scriptPubKey);
            $vectors[] = [
                $flags, $scriptSig, $scriptPubKey, $test->result, $test->desc, new Transaction
            ];
        }

        $flags = new ScriptInterpreterFlags(0);
        $vectors[] = [
            $flags,
            new Script(),
            ScriptFactory::create()->push(Buffer::hex(file_get_contents(__DIR__ . "/../Data/10010bytes.hex"))),
            false,
            'fails with >10000 bytes',
            new Transaction
        ];

        return $vectors;
    }


    /**
     * @dataProvider getScripts
     * @param ScriptInterpreterFlags $flags
     * @param ScriptInterface $scriptSig
     * @param ScriptInterface $scriptPubKey
     */
    public function testScript(ScriptInterpreterFlags $flags, ScriptInterface $scriptSig, ScriptInterface $scriptPubKey, $result, $description, $tx)
    {
        $i = new ScriptInterpreter(Bitcoin::getEcAdapter(), $tx, $flags);

        $i->setScript($scriptSig)->run();
        $testResult = $i->setScript($scriptPubKey)->run();

        $this->assertEquals($result, $testResult, $description);
    }/**/
}
