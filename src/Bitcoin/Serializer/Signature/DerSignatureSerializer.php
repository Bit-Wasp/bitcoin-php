<?php

namespace BitWasp\Bitcoin\Serializer\Signature;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Exceptions\ParserOutOfRange;
use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Signature\Signature;
use BitWasp\Bitcoin\Signature\SignatureInterface;

class DerSignatureSerializer
{

    /**
     * @var Math
     */
    private $math;

    /**
     * @param Math $math
     */
    public function __construct(Math $math)
    {
        $this->math = $math;
    }

    /**
     * @param SignatureInterface $signature
     * @return Buffer
     */
    public function serialize(SignatureInterface $signature)
    {
        $math = $this->math;

        // Ensure that the R and S hex's are of even length
        $rBin = pack('H*', $math->decHex($signature->getR()));
        $sBin = pack('H*', $math->decHex($signature->getS()));

        // Pad R and S if their highest bit is flipped, ie,
        // they are negative.
        $rt = $rBin[0] & pack('H*', '80');
        if (ord($rt) == 128) {
            $rBin = pack('H*', '00') . $rBin;
        }

        $st = $sBin[0] & pack('H*', '80');
        if (ord($st) == 128) {
            $sBin = pack('H*', '00') . $sBin;
        }

        $inner = new Parser();
        $inner
            ->writeBytes(1, '02')
            ->writeWithLength(new Buffer($rBin))
            ->writeBytes(1, '02')
            ->writeWithLength(new Buffer($sBin));

        $outer = new Parser();
        $outer
            ->writeBytes(1, '30')
            ->writeWithLength($inner->getBuffer())
            ->writeInt(1, $signature->getSighashType(), true);

        $serialized = $outer->getBuffer();

        return $serialized;
    }

    /**
     * @param Parser $parser
     * @return Signature
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser & $parser)
    {
        try {
            $parser->readBytes(1);
            $outer    = $parser->getVarString();
            $sighash = $parser->readBytes(1)->getInt();

            $parse    = new Parser($outer);
            $parse->readBytes(1);
            $r = $parse->getVarString()->getInt();

            $parse->readBytes(1);
            $s = $parse->getVarString()->getInt();
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full signature from parser');
        }

        $signature = new Signature($r, $s, $sighash);
        return $signature;
    }

    /**
     * @param $string
     * @return Signature
     * @throws ParserOutOfRange
     */
    public function parse($string)
    {
        $parser = new Parser($string);
        $signature = $this->fromParser($parser);
        return $signature;
    }
}
