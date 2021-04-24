<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Serializer\Key\HierarchicalKey;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;

class ExtendedKeySerializerTest extends AbstractTestCase
{
    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $adapter
     */
    public function testInvalidKey(EcAdapterInterface $adapter)
    {
        $network = NetworkFactory::bitcoinTestnet();
        $serializer = new ExtendedKeySerializer($adapter);
        $this->expectException(ParserOutOfRange::class);
        $this->expectExceptionMessage("Failed to extract HierarchicalKey from parser");
        $serializer->parse($network, new Buffer());
    }
}
