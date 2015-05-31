<?php

namespace BitWasp\Bitcoin\Tests\Serializer\Signature;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializer\Signature\DerSignatureSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Parser;

class DerSignatureSerializerTest extends AbstractTestCase
{
    /**
     * @expectedException \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function testFromParserFailure()
    {
        $math = new Math();
        $serializer = new DerSignatureSerializer($math);
        $parser = new Parser();

        $serializer->fromParser($parser);
    }
}
