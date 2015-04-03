<?php

namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Serializer\Transaction\TransactionOutputSerializer;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;

class TransactionOutputTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TransactionOutput
     */
    protected $out;
    protected $txOutType;
    protected $scriptType;
    protected $bufferType;
    /**
     * @var TransactionOutputSerializer
     */
    protected $serializer;

    public function __construct()
    {
        $this->txOutType = 'BitWasp\Bitcoin\Transaction\TransactionOutput';
        $this->scriptType = 'BitWasp\Bitcoin\Script\Script';
        $this->bufferType = 'BitWasp\Buffertools\Buffer';
    }

    public function setUp()
    {
        $this->out = new TransactionOutput();
        $this->serializer = new TransactionOutputSerializer();
    }

    public function testGetValueDefault()
    {
        $this->assertSame('0', $this->out->getValue());
    }

    public function testSetValue()
    {
        $this->out->setValue(1);
        $this->assertSame(1, $this->out->getValue());
    }


    public function testGetScript()
    {
        $script = $this->out->getScript();
        $this->assertInstanceOf($this->scriptType, $script);
        $this->assertEmpty($script->getBuffer()->getBinary());
    }

    public function testSetScript()
    {
        $script = new Script();
        $script = $script->op('OP_2')->op('OP_3');

        $this->out->setScript($script);
        $this->assertSame($script, $this->out->getScript());
    }

    public function testConstructWithScript()
    {
        $t = new TransactionOutput();
        $this->assertEquals('0', $t->getValue());
        $this->assertEquals((new Script(new Buffer)), $t->getScript());

        $scriptBuf = new Buffer('03010203');
        $script = new Script();
        $script->push($scriptBuf);
        $value = 100000000;



        $t = new TransactionOutput(null, $script);
        $this->assertEquals($script, $t->getScript());

        $t = new TransactionOutput($value, null);
        $this->assertSame($value, $t->getValue());
    }

    public function testFromParser()
    {
        $buffer = Buffer::hex('cac10000000000001976a9140eff868646ece0af8bc979093585e80297112f1f88ac');
        $parser = new Parser($buffer);
        $out = $this->serializer->fromParser($parser);
        $this->assertInstanceOf($this->txOutType, $out);
    }

    public function testSerialize()
    {
        $buffer = 'cac10000000000001976a9140eff868646ece0af8bc979093585e80297112f1f88ac';
        $s = new TransactionOutputSerializer();
        $out = $s->parse($buffer);
        $this->assertEquals($buffer, $out->getBuffer()->getHex());
    }

};
