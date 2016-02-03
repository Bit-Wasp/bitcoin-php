<?php

namespace BitWasp\Bitcoin\Tests\Serializer\Block;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Block\BlockSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\MTransactionSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class BlockSerializerTest extends AbstractTestCase
{
    /**
     * @expectedException \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function testInvalidParse()
    {
        $serializer = new BlockSerializer(
            new Math,
            new BlockHeaderSerializer,
            new MTransactionSerializer
        );
        $serializer->parse('');
    }
}
