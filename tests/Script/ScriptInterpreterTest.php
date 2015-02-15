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
    public function testGetOpValid()
    {
        $i = new ScriptInterpreter($this->math, $this->G, new Transaction(), new ScriptInterpreterFlags());

        $script = pack("H*", '0100');
        $position = 0;
        $end = 2;
        $opCode = null;
        $pushdata = null;
        $this->assertTrue($i->getOp($script, $position, $end, $opCode, $pushdata));
        $this->assertSame(1, $opCode);
        $this->assertSame(chr(0), $pushdata);

        $s = '';
        for ($j = 1; $j < 256; $j++)
            $s .= '41';
        $script = pack("H*", '4cff'.$s);
        $position = 0;
        $end = strlen($script);
        $opCode = null;
        $pushdata = null;
        $this->assertTrue($i->getOp($script, $position, $end, $opCode, $pushdata));
        $this->assertSame(76, $opCode);
        $this->assertSame(pack("H*", $s), $pushdata);

        $s = '';
        for ($j = 1; $j < 260; $j++)
            $s .= '41';
        $script = pack("cvH*", 0x4d, 260, $s);
        $position = 0;
        $end = strlen($script)+1;
        $opCode = null;
        $pushdata = null;
        $this->assertTrue($i->getOp($script, $position, $end, $opCode, $pushdata));
        $this->assertSame(77, $opCode);
        $this->assertSame(pack("H*", $s), $pushdata);
    }

    public function testGetOpInvalid()
    {
        $i = new ScriptInterpreter($this->math, $this->G, new Transaction(), new ScriptInterpreterFlags());

        $script = '';
        $position = 10;
        $end = 0;
        $opCode = null;
        $pushdata = null;
        $this->assertFalse($i->getOp($script, $position, $end, $opCode, $pushdata));

        $position = 11;
        $end = -1;
        $opCode = null;
        $pushdata = null;
        $this->assertFalse($i->getOp($script, $position, $end, $opCode, $pushdata));

        // Test a failure - should return false since there aren't two bytes
        $script = pack("H*", '0200');
        $position = 0;
        $end = 2;
        $opCode = null;
        $pushdata = null;
        $this->assertFalse($i->getOp($script, $position, $end, $opCode, $pushdata));
        $this->assertSame(2, $opCode);
        $this->assertSame(null, $pushdata);

        // Test a failure - pushdata without length or string
        $script = pack("H*", '4c');
        $position = 0;
        $end = strlen($script);
        $opCode = null;
        $pushdata = null;
        $this->assertFalse($i->getOp($script, $position, $end, $opCode, $pushdata));
        $this->assertSame(76, $opCode);
        $this->assertSame(null, $pushdata);

        // pushdata size (249) is less than length (255)
        $s = '';
        for ($j = 1; $j < 250; $j++)
            $s .= '41';
        $script = pack("H*", '4cff'.$s);
        $position = 0;
        $end = strlen($script);
        $opCode = null;
        $pushdata = null;
        $this->assertFalse($i->getOp($script, $position, $end, $opCode, $pushdata));
        $this->assertSame(76, $opCode);
        $this->assertSame(null, $pushdata);

    }

    public function testScript()
    {
        $f = file_get_contents(__DIR__ . '/../Data/scriptinterpreter.simple.json');
        $json = json_decode($f);

        // Pass a dummy transaction since not testing OP_CHECKSIG

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
