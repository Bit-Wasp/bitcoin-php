<?php

namespace Bitcoin\Tests\Transaction;

use Bitcoin\Transaction\TransactionInput;
use Bitcoin\Script\Script;
use Bitcoin\Util\Parser;
use Bitcoin\Util\Buffer;

class TransactionInputTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var TransactionInput
     */
    protected $in;

    protected $baseType;
    protected $scriptType;

    public function __construct()
    {
        $this->baseType = 'Bitcoin\Transaction\TransactionInput';
        $this->scriptType = 'Bitcoin\Script\Script';
    }

    public function setUp()
    {
        $this->in = new TransactionInput();
    }

    public function testGetTransactionId()
    {
        $this->assertNull($this->in->getTransactionId());
    }

    public function testSetTransactionId()
    {
        $this->in->setTransactionId('7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33');
        $this->assertSame('7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33', $this->in->getTransactionId());
    }

    public function testGetVout()
    {
        $this->assertNull($this->in->getVout());
    }

    public function testSetVout()
    {
        $this->in->setVout(0);
        $this->assertSame(0, $this->in->getVout());
    }

    public function testGetSequence()
    {
        $this->assertSame(0xffffffff, $this->in->getSequence());
    }

    public function testSetSequence()
    {
        $this->in->setSequence(10240);
        $this->assertSame(10240, $this->in->getSequence());
    }

    public function testGetScriptBuf()
    {
        $this->assertNull($this->in->getScriptBuf());
    }

    public function testSetScriptBuf()
    {
        $script = new Script();
        $script = $script->op('OP_2')->op('OP_3')->serialize();
        $buffer = new Buffer($script);
        $this->in->setScriptBuf($buffer);
        $this->assertSame($script, $this->in->getScriptBuf()->serialize());
    }

    public function testGetScript()
    {
        $script = $this->in->getScript();
        $this->assertInstanceOf($this->scriptType, $script);
        $this->assertEmpty($script->serialize());
    }

    public function testIsCoinbase()
    {
        $this->in->setTransactionId('7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33');
        $this->assertFalse($this->in->isCoinbase());

        $this->in->setTransactionId('0000000000000000000000000000000000000000000000000000000000000000');
        $this->assertTrue($this->in->isCoinbase());
    }

    public function testFromParser()
    {
        $buffer = Buffer::hex('62442ea8de9ee6cc2dd7d76dfc4523910eb2e3bd4b202d376910de700f63bf4b000000008b48304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b014104f8de51f3b278225c0fe74a856ea2481e9ad4c9385fc10cefadaa4357ecd2c4d29904902d10e376546500c127f65d0de35b6215d49dd1ef6c67e6cdd5e781ef22ffffffff');
        $parser = new Parser($buffer);
        $in     = $this->in->fromParser($parser);
        $this->assertInstanceOf($this->baseType, $in);
    }

    public function testSerialize()
    {
        $hex    = '62442ea8de9ee6cc2dd7d76dfc4523910eb2e3bd4b202d376910de700f63bf4b000000008b48304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b014104f8de51f3b278225c0fe74a856ea2481e9ad4c9385fc10cefadaa4357ecd2c4d29904902d10e376546500c127f65d0de35b6215d49dd1ef6c67e6cdd5e781ef22ffffffff';
        $buffer = Buffer::hex($hex);
        $parser = new Parser($buffer);
        $in     = $this->in->fromParser($parser);
        $this->assertSame($hex, $in->serialize('hex'));
    }

    public function testGetSize()
    {
        $hex    = '62442ea8de9ee6cc2dd7d76dfc4523910eb2e3bd4b202d376910de700f63bf4b000000008b48304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b014104f8de51f3b278225c0fe74a856ea2481e9ad4c9385fc10cefadaa4357ecd2c4d29904902d10e376546500c127f65d0de35b6215d49dd1ef6c67e6cdd5e781ef22ffffffff';
        $buffer = Buffer::hex($hex);
        $parser = new Parser($buffer);
        $in     = $this->in->fromParser($parser);
        $this->assertSame(180, $this->in->getSize());
        $this->assertSame(360, $this->in->getSize('hex'));
    }

    public function test__toString()
    {
        $hex    = '62442ea8de9ee6cc2dd7d76dfc4523910eb2e3bd4b202d376910de700f63bf4b000000008b48304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b014104f8de51f3b278225c0fe74a856ea2481e9ad4c9385fc10cefadaa4357ecd2c4d29904902d10e376546500c127f65d0de35b6215d49dd1ef6c67e6cdd5e781ef22ffffffff';
        $buffer = Buffer::hex($hex);
        $parser = new Parser($buffer);
        $in     = $this->in->fromParser($parser);
        $this->assertSame($hex, $this->in->__toString('hex'));
    }

}