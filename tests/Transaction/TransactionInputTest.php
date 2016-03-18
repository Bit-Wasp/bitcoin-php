<?php

namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionInputSerializer;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Buffertools\Buffer;

class TransactionInputTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $baseType = 'BitWasp\Bitcoin\Transaction\TransactionInput';

    /**
     * @var string
     */
    private $scriptType = 'BitWasp\Bitcoin\Script\Script';

    public function testGetSequence()
    {
        // test default
        $in = new TransactionInput(new OutPoint(Buffer::hex('7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33', 32), '0'), new Script());
        $this->assertSame(0xffffffff, $in->getSequence());
        $this->assertTrue($in->isFinal());

        // test when set
        $in = new TransactionInput(new OutPoint(Buffer::hex('7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33', 32), '0'), new Script(), 23);
        $this->assertSame(23, $in->getSequence());
        $this->assertFalse($in->isFinal());
    }

    public function testConstructWithScript()
    {
        $txid = Buffer::hex('7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33', 32);
        $vout = '0';
        $outpoint = new OutPoint($txid, $vout);

        $scriptBuf = new Buffer('03010203');
        $script = ScriptFactory::create()->push($scriptBuf)->getScript();
        $sequence = '0';

        $t = new TransactionInput($outpoint, $script, $sequence);
        $this->assertSame($outpoint, $t->getOutPoint());
        $this->assertSame($script, $t->getScript());
        $this->assertSame($sequence, $t->getSequence());
        $this->assertEquals($outpoint, $t['outpoint']);
        $this->assertSame($script, $t['script']);
        $this->assertSame($sequence, $t['sequence']);

    }

    public function testGetScript()
    {
        $script = new Script(Buffer::hex('41'));
        $in = new TransactionInput(new OutPoint(Buffer::hex('7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33', 32), '0'), $script);

        $this->assertInstanceOf($this->scriptType, $in->getScript());
        $this->assertEquals($script, $in->getScript());
    }

    public function testIsCoinbase()
    {
        $in = new TransactionInput(new OutPoint(Buffer::hex('7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33'), '0'), new Script());
        $this->assertFalse($in->isCoinbase());

        $in = new TransactionInput(new OutPoint(Buffer::hex('7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33'), 4294967295), new Script());
        $this->assertFalse($in->isCoinbase());

        $in = new TransactionInput(new OutPoint(Buffer::hex('0000000000000000000000000000000000000000000000000000000000000000'), 0), new Script());
        $this->assertFalse($in->isCoinbase());

        $in = new TransactionInput(new OutPoint(Buffer::hex('0000000000000000000000000000000000000000000000000000000000000000'), 4294967295), new Script());
        $this->assertTrue($in->isCoinbase());
    }

    public function testEquals()
    {
        $in1 = new TransactionInput(new OutPoint(Buffer::hex('0000000000000000000000000000000000000000000000000000000000000000'), 4294967295), new Script(), 1);
        $in1eq = new TransactionInput(new OutPoint(Buffer::hex('0000000000000000000000000000000000000000000000000000000000000000'), 4294967295), new Script(), 1);

        $inBadOut = new TransactionInput(new OutPoint(Buffer::hex('0000000000000000000000000000000000000000000000000000000000000000'), 1), new Script(), 1);
        $inBadScript = new TransactionInput(new OutPoint(Buffer::hex('0000000000000000000000000000000000000000000000000000000000000000'), 4294967295), new Script(new Buffer('a')), 1);
        $inBadSeq = new TransactionInput(new OutPoint(Buffer::hex('0000000000000000000000000000000000000000000000000000000000000000'), 4294967295), new Script(), 123123);

        $this->assertTrue($in1->equals($in1eq));
        $this->assertFalse($in1->equals($inBadOut));
        $this->assertFalse($in1->equals($inBadScript));
        $this->assertFalse($in1->equals($inBadSeq));
    }

    public function testFromParser()
    {
        $buffer = '62442ea8de9ee6cc2dd7d76dfc4523910eb2e3bd4b202d376910de700f63bf4b000000008b48304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b014104f8de51f3b278225c0fe74a856ea2481e9ad4c9385fc10cefadaa4357ecd2c4d29904902d10e376546500c127f65d0de35b6215d49dd1ef6c67e6cdd5e781ef22ffffffff';
        $s = new TransactionInputSerializer(new OutPointSerializer());
        $in = $s->parse($buffer);
        $this->assertInstanceOf($this->baseType, $in);
    }

    public function testSerialize()
    {
        $hex = '62442ea8de9ee6cc2dd7d76dfc4523910eb2e3bd4b202d376910de700f63bf4b000000008b48304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b014104f8de51f3b278225c0fe74a856ea2481e9ad4c9385fc10cefadaa4357ecd2c4d29904902d10e376546500c127f65d0de35b6215d49dd1ef6c67e6cdd5e781ef22ffffffff';
        $s = new TransactionInputSerializer(new OutPointSerializer());
        $in = $s->parse($hex);
        $this->assertEquals($hex, $in->getBuffer()->getHex());
    }
}
