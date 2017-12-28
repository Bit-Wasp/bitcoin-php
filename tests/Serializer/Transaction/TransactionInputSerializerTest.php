<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Serializer\Transaction;

use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionInputSerializer;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class TransactionInputSerializerTest extends AbstractTestCase
{
    public function testFromParser()
    {
        $buffer = Buffer::hex('62442ea8de9ee6cc2dd7d76dfc4523910eb2e3bd4b202d376910de700f63bf4b000000008b48304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b014104f8de51f3b278225c0fe74a856ea2481e9ad4c9385fc10cefadaa4357ecd2c4d29904902d10e376546500c127f65d0de35b6215d49dd1ef6c67e6cdd5e781ef22ffffffff');
        $s = new TransactionInputSerializer(new OutPointSerializer());
        $in = $s->parse($buffer);
        $this->assertInstanceOf(TransactionInput::class, $in);
        $this->assertInstanceOf(TransactionInputInterface::class, $in);
    }

    public function testSerialize()
    {
        $hex = Buffer::hex('62442ea8de9ee6cc2dd7d76dfc4523910eb2e3bd4b202d376910de700f63bf4b000000008b48304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b014104f8de51f3b278225c0fe74a856ea2481e9ad4c9385fc10cefadaa4357ecd2c4d29904902d10e376546500c127f65d0de35b6215d49dd1ef6c67e6cdd5e781ef22ffffffff');
        $s = new TransactionInputSerializer(new OutPointSerializer());
        $in = $s->parse($hex);
        $this->assertTrue($hex->equals($in->getBuffer()));
        $this->assertTrue($hex->equals($s->serialize($in)));
        $this->assertTrue($hex->equals($in->getBuffer()));
    }
}
