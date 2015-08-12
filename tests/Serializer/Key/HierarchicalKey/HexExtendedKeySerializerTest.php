<?php

namespace BitWasp\Bitcoin\Tests\Serializer\Key\HierarchicalKey;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\HexExtendedKeySerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class HexExtendedKeySerializerTest extends AbstractTestCase
{
    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $adapter
     * @expectedException \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function testInvalidKey(EcAdapterInterface $adapter)
    {
        $network = NetworkFactory::bitcoinTestnet();
        $serializer = new HexExtendedKeySerializer($adapter, $network);
        $serializer->parse(new Buffer());
    }
}
