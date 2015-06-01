<?php

namespace BitWasp\Bitcoin\Tests\Serializer\Signature;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializer\Signature\CompactSignatureSerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class CompactSignatureTest extends AbstractTestCase
{
    /**
     * @expectedException \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function testFromParserFailure()
    {
        $math = new Math();
        $serializer = new CompactSignatureSerializer($math);
        $parser = new Parser();

        $serializer->fromParser($parser);
    }

    public function testValidRecovery()
    {
        $math = new Math();
        $r = str_pad('', 64, '4');
        $s = str_pad('', 64, '5');
        $serializer = new CompactSignatureSerializer($math);

        for ($c = 0; $c <= 7; $c++) {
            $t = $math->add($c, 27);
            $test = Buffer::hex($math->decHex($t) . $r . $s);
            $parsed = $serializer->parse($test);
            $this->assertInstanceOf('BitWasp\Bitcoin\Signature\CompactSignature', $parsed);
        }
    }

    public function getInvalidRecoveryFlag()
    {
        return [[-1], [8]];
    }

    /**
     * @dataProvider getInvalidRecoveryFlag
     * @param int $c
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage invalid signature type
     */
    public function testInvalidRecovery($c)
    {
        $math = new Math();
        $r = str_pad('', 64, '4');
        $s = str_pad('', 64, '5');
        $serializer = new CompactSignatureSerializer($math);

        $t = $math->add($c, 27);
        $test = Buffer::hex($math->decHex($t) . $r . $s);
        $serializer->parse($test);
    }
}
