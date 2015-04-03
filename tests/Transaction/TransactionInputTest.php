<?php

namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Serializer\Transaction\TransactionInputSerializer;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Buffer;

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
        $this->baseType = 'BitWasp\Bitcoin\Transaction\TransactionInput';
        $this->scriptType = 'BitWasp\Bitcoin\Script\Script';
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

    public function testConstructWithScript()
    {
        $t = new TransactionInput();
        $this->assertNull($t->getTransactionId());
        $this->assertNull($t->getVout());
        $this->assertNull($t->getTransactionId());

        $txid = '7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33';
        $vout = '0';
        $scriptBuf = new Buffer('03010203');
        $script = new Script();
        $script->push($scriptBuf);
        $sequence = '0';
        $t = new TransactionInput($txid);
        $this->assertSame($txid, $t->getTransactionId());

        $t = new TransactionInput(null, $vout);
        $this->assertSame($vout, $t->getVout());

        $t = new TransactionInput(null, null, $script);
        $this->assertSame($script, $t->getScript());

        $t = new TransactionInput(null, null, null, $sequence);
        $this->assertSame('0', $t->getSequence());
    }

    public function testSetSequence()
    {
        $this->in->setSequence(10240);
        $this->assertSame(10240, $this->in->getSequence());
    }

    public function testGetScript()
    {
        $script = $this->in->getScript();
        $this->assertInstanceOf($this->scriptType, $script);
        $this->assertEmpty($script->getBuffer()->getBinary());
    }

    public function testIsCoinbase()
    {
        $this->in = new TransactionInput();
        $this->in->setTransactionId('7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33');
        $this->in->setVout('0');
        $this->assertFalse($this->in->isCoinbase());

        $this->in = new TransactionInput();
        $this->in->setTransactionId('7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33');
        $this->in->setVout(4294967295);
        $this->assertFalse($this->in->isCoinbase());

        $this->in = new TransactionInput();
        $this->in->setTransactionId('0000000000000000000000000000000000000000000000000000000000000000');
        $this->in->setVout(0);
        $this->assertFalse($this->in->isCoinbase());

        $this->in = new TransactionInput();
        $this->in->setTransactionId('0000000000000000000000000000000000000000000000000000000000000000');
        $this->in->setVout(4294967295);

        $this->assertTrue($this->in->isCoinbase());
    }

    public function testFromParser()
    {
        $buffer = '62442ea8de9ee6cc2dd7d76dfc4523910eb2e3bd4b202d376910de700f63bf4b000000008b48304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b014104f8de51f3b278225c0fe74a856ea2481e9ad4c9385fc10cefadaa4357ecd2c4d29904902d10e376546500c127f65d0de35b6215d49dd1ef6c67e6cdd5e781ef22ffffffff';
        $s = new TransactionInputSerializer();
        $in = $s->parse($buffer);
        $this->assertInstanceOf($this->baseType, $in);
    }

    public function testSerialize()
    {
        $hex    = '62442ea8de9ee6cc2dd7d76dfc4523910eb2e3bd4b202d376910de700f63bf4b000000008b48304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b014104f8de51f3b278225c0fe74a856ea2481e9ad4c9385fc10cefadaa4357ecd2c4d29904902d10e376546500c127f65d0de35b6215d49dd1ef6c67e6cdd5e781ef22ffffffff';
        $s = new TransactionInputSerializer();
        $in = $s->parse($hex);
        $this->assertEquals($hex, $in->getBuffer()->getHex());
    }

}
