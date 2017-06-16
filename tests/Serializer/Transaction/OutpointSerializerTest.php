<?php

namespace BitWasp\Bitcoin\Tests\Serializer\Transaction;

use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Buffertools\Buffer;

class OutpointSerializerTest extends AbstractTestCase
{
    public function testOutpointSerializer()
    {
        $txid = new Buffer('a', 32);
        $vout = 10;
        $outpoint = new OutPoint($txid, $vout);

        $serialized = $txid->flip()->getBinary() . pack('V', $vout);
        $this->assertEquals($serialized, $outpoint->getBuffer()->getBinary());

        $serializer = new OutPointSerializer();
        $serializerOutput = $serializer->serialize($outpoint);
        $this->assertEquals($serialized, $serializerOutput->getBinary());

        $parsed = $serializer->parse($serializerOutput);
        $this->assertTrue($parsed->equals($outpoint));
    }
}
