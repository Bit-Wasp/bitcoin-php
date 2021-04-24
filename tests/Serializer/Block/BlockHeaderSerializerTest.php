<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Serializer\Block;

use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;

class BlockHeaderSerializerTest extends AbstractTestCase
{
    public function testInvalidParse()
    {
        $serializer = new BlockHeaderSerializer;
        $this->expectException(ParserOutOfRange::class);
        $serializer->parse(new Buffer());
    }
}
