<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\CompactSignatureInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class CompactSignatureSerializerTest extends AbstractTestCase
{
    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @expectedException \Exception
     */
    public function testFromParserFailure(EcAdapterInterface $ecAdapter)
    {
        /** @var CompactSignatureSerializerInterface $serializer */
        $serializer = EcSerializer::getSerializer(CompactSignatureSerializerInterface::class, true, $ecAdapter);
        $serializer->parse(new Buffer());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testValidRecovery(EcAdapterInterface $ecAdapter)
    {
        $r = str_pad('', 64, '4');
        $s = str_pad('', 64, '5');
        /** @var CompactSignatureSerializerInterface $serializer */
        $serializer = EcSerializer::getSerializer(CompactSignatureSerializerInterface::class, true, $ecAdapter);

        $math = $ecAdapter->getMath();
        for ($c = 1; $c < 5; $c++) {
            $t = $c + 27;
            $test = Buffer::hex($math->decHex((string) $t) . $r . $s);
            $parsed = $serializer->parse($test);
            $this->assertInstanceOf(CompactSignatureInterface::class, $parsed);
        }
    }

    public function getInvalidRecoveryFlag()
    {
        return [[-1], [8]];
    }
}
