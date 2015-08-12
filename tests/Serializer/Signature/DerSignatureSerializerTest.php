<?php

namespace BitWasp\Bitcoin\Tests\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class DerSignatureSerializerTest extends AbstractTestCase
{
    /**
     * @dataProvider getEcAdapters
     * @expectedException \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function testFromParserFailure(EcAdapterInterface $adapter)
    {
        $serializer = EcSerializer::getSerializer($adapter, DerSignatureSerializerInterface::class);
        /** @var DerSignatureSerializerInterface $serializer */
        $serializer->parse('');
    }
}
