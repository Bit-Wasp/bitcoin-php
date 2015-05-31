<?php

namespace BitWasp\Bitcoin\Tests\Serializer\Key\HierarchicalKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\PhpEcc;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\HexExtendedKeySerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use Mdanter\Ecc\EccFactory;

class HexExtendedKeySerializerTest extends AbstractTestCase
{
    /**
     * @expectedException \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function testInvalidKey()
    {
        $math = new Math();
        $generator = EccFactory::getSecgCurves()->generator256k1();
        $network = NetworkFactory::bitcoinTestnet();
        $serializer = new HexExtendedKeySerializer(new PhpEcc($math, $generator), $network);
        $serializer->parse(new Buffer());
    }
}