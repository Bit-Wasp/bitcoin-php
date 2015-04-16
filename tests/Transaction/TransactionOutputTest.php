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
     * @var string
     */
    private $txOutType = 'BitWasp\Bitcoin\Transaction\TransactionOutput';
    /**
     * @var string
     */
    private $scriptType = 'BitWasp\Bitcoin\Script\Script';

    /**
     * @var TransactionOutputSerializer
     */
    protected $serializer;

    public function setUp()
    {

        $this->serializer = new TransactionOutputSerializer();
    }

    public function testGetValueDefault()
    {
        $out = new TransactionOutput('1', new Script());
        $this->assertSame('1', $out->getValue());

        $out = new TransactionOutput(10901, new Script());
        $this->assertSame(10901, $out->getValue());
    }

    public function testGetScript()
    {
        $out = new TransactionOutput(1, new Script());
        $script = $out->getScript();
        $this->assertInstanceOf($this->scriptType, $script);
        $this->assertEmpty($script->getBuffer()->getBinary());
    }

    public function testSetScript()
    {
        $script = new Script();
        $script = $script->op('OP_2')->op('OP_3');

        $out = new TransactionOutput(1, $script);
        $out->setScript($script);
        $this->assertSame($script, $out->getScript());
    }

    public function testFromParser()
    {
        $buffer = Buffer::hex('cac10000000000001976a9140eff868646ece0af8bc979093585e80297112f1f88ac');
        $parser = new Parser($buffer);
        $s = new TransactionOutputSerializer();
        $out = $s->fromParser($parser);
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
