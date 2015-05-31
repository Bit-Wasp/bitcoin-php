<?php

namespace BitWasp\Bitcoin\Tests\Serializer\Block;


use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Block\HexBlockSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class HexBlockSerializerTest extends AbstractTestCase
{
    /**
     * @expectedException \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function testInvalidParse()
    {
        $serializer = new HexBlockSerializer(
            new Math,
            new HexBlockHeaderSerializer,
            new TransactionSerializer
        );
        $serializer->parse('');
    }
}