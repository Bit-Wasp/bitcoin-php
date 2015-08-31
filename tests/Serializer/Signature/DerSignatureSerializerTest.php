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
     * @param EcAdapterInterface $adapter
     * @expectedException \Exception
     */
    public function testFromParserFailure(EcAdapterInterface $adapter)
    {
        $serializer = EcSerializer::getSerializer($adapter, 'BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface');
        /** @var DerSignatureSerializerInterface $serializer */
        $serializer->parse('');
    }
}
