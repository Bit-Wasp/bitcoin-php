<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 06/02/15
 * Time: 09:38
 */

namespace Bitcoin\Tests\Script;

use Bitcoin\Bitcoin;
use Bitcoin\Buffer;
use Bitcoin\Script\Script;
use Bitcoin\Script\ScriptInterpreter;
use Bitcoin\Transaction\Transaction;
use Bitcoin\Script\ScriptInterpreterFlags;

class ScriptInterpreterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Bitcoin\Math\Math
     */
    public $math;

    /**
     * @var \Mdanter\Ecc\GeneratorPoint
     */
    public $G;

    public function __construct()
    {
        $this->math = Bitcoin::getMath();
        $this->G = Bitcoin::getGenerator();
    }

    public function setUp()
    {

    }

    private function setFlags($flagStr)
    {
        $array = explode(",", $flagStr);
        $flags = new ScriptInterpreterFlags();
        foreach ($array as $activeFlag) {
            $flags->$activeFlag = true;
        }
        return $flags;
    }

    public function ootestScriptindividual()
    {
        $script = new Script(Buffer::hex('4d010008010887'));

        echo "try script: ".$script->getAsm()."\n";
        echo "    script: ".$script->serialize('hex')."\n";

        $flags = $this->setFlags('verifyP2SH,verifyStrictEncoding');
        $i = new ScriptInterpreter($this->math, $this->G, new Transaction(), $flags);

        $i->setScript($script);

        $return = $i->run();
        $this->assertTrue($return);

    }

    public function testScript()
    {
        $f = file_get_contents(__DIR__ . '/../Data/scriptinterpreter.simple.json');
        $json = json_decode($f);

        // Pass with a dummy transaction since not testing OP_CHECKSIG


        foreach ($json->test as $test) {
            $flags = $this->setFlags($test->flags);
            $i = new ScriptInterpreter($this->math, $this->G, new Transaction(), $flags);
            $scriptSig = new Script(Buffer::hex($test->scriptSig));
            $scriptPubKey = new Script(Buffer::hex($test->scriptPubKey));

            $i->setScript($scriptSig)->run();
            $testResult = $i->setScript($scriptPubKey)->run();

            $this->assertTrue($testResult, $test->result);

        }
    }
}
