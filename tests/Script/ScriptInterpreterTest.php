<?php

namespace Afk11\Bitcoin\Tests\Script;

use \Afk11\Bitcoin\Bitcoin;
use \Afk11\Bitcoin\Buffer;
use \Afk11\Bitcoin\Script\Script;
use Afk11\Bitcoin\Script\ScriptFactory;
use \Afk11\Bitcoin\Script\ScriptInterpreter;
use \Afk11\Bitcoin\Transaction\TransactionFactory;
use \Afk11\Bitcoin\Script\ScriptInterpreterFlags;

class ScriptInterpreterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Afk11\Bitcoin\Math\Math
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

    public function testScript()
    {
        $f = file_get_contents(__DIR__ . '/../Data/scriptinterpreter.simple.json');
        $json = json_decode($f);

        // Pass a dummy transaction since not testing OP_CHECKSIG

        foreach ($json->test as $c => $test) {
            $flags = $this->setFlags($test->flags);
            $i = new ScriptInterpreter($this->math, $this->G, TransactionFactory::create(), $flags);
            $scriptSig = ScriptFactory::fromHex($test->scriptSig);
            $scriptPubKey = ScriptFactory::fromHex($test->scriptPubKey);

            $i->setScript($scriptSig)->run();
            $testResult = $i->setScript($scriptPubKey)->run();

            $this->assertTrue($testResult, $test->result);

        }
    }

}
