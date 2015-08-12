<?php

namespace BitWasp\Bitcoin\Tests\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class CompactSignatureTest extends AbstractTestCase
{
    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @expectedException \Exception
     */
    public function testFromParserFailure(EcAdapterInterface $ecAdapter)
    {
        $serializer = EcSerializer::getSerializer($ecAdapter, 'BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface');
        /** @var CompactSignatureSerializerInterface $serializer */
        $serializer->parse('');
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testValidRecovery(EcAdapterInterface $ecAdapter)
    {
        $r = str_pad('', 64, '4');
        $s = str_pad('', 64, '5');
        $serializer = EcSerializer::getSerializer($ecAdapter, 'BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface');
        /** @var CompactSignatureSerializerInterface $serializer */

        $math = $ecAdapter->getMath();
        for ($c = 1; $c < 5; $c++) {
            $t = $math->add($c, 27);
            $test = Buffer::hex($math->decHex($t) . $r . $s);
            $parsed = $serializer->parse($test);
            $this->assertInstanceOf('BitWasp\Bitcoin\Crypto\EcAdapter\Signature\CompactSignatureInterface', $parsed);
        }
    }

    public function getInvalidRecoveryFlag()
    {
        return [[-1], [8]];
    }
}
