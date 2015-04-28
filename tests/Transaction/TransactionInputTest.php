<?php

namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Serializer\Transaction\TransactionInputSerializer;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Script\Script;
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
        $in = new TransactionInput('7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33', '0');
        $this->assertSame(0xffffffff, $in->getSequence());
        $in->setSequence(23);
        $this->assertSame(23, $in->getSequence());
    }

    public function testConstructWithScript()
    {

        $txid = '7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33';
        $vout = '0';
        $scriptBuf = new Buffer('03010203');
        $script = new Script();
        $script->push($scriptBuf);
        $sequence = '0';
        $t = new TransactionInput($txid, $vout, $script, $sequence);
        $this->assertSame($txid, $t->getTransactionId());
        $this->assertSame($vout, $t->getVout());
        $this->assertSame($script, $t->getScript());
        $this->assertSame($sequence, $t->getSequence());
    }

    public function testGetScript()
    {
        $in = new TransactionInput('7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33', '0');
        $script = $in->getScript();
        $this->assertInstanceOf($this->scriptType, $script);
        $this->assertEmpty($script->getBuffer()->getBinary());
    }

    public function testIsCoinbase()
    {
        $in = new TransactionInput('7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33', '0');
        $this->assertFalse($in->isCoinbase());

        $in = new TransactionInput('7f8e94bdf85de933d5417145e4b76926777fa2a2d8fe15b684cfd835f43b8b33', 4294967295);
        $this->assertFalse($in->isCoinbase());

        $in = new TransactionInput('0000000000000000000000000000000000000000000000000000000000000000', 0);
        $this->assertFalse($in->isCoinbase());

        $in = new TransactionInput('0000000000000000000000000000000000000000000000000000000000000000', 4294967295);
        $this->assertTrue($in->isCoinbase());
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
