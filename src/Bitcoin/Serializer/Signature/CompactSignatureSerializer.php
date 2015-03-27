<?php

namespace BitWasp\Bitcoin\Serializer\Signature;

use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Exceptions\ParserOutOfRange;
use BitWasp\Bitcoin\Signature\CompactSignature;

class CompactSignatureSerializer
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
     * @param CompactSignature $signature
     * @return Buffer
     */
    public function serialize(CompactSignature $signature)
    {
        $math = $this->math;

        $val = $signature->getFlags();

        $parser = new Parser;
        $parser
            ->writeInt(1, $val)
            ->writeBytes(32, $math->decHex($signature->getR()))
            ->writeBytes(32, $math->decHex($signature->getS()));

        return $parser->getBuffer();
    }

    /**
     * @param Parser $parser
     * @return CompactSignature
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser & $parser)
    {
        try {
            list ($byte, $r, $s) = [
                $parser->readBytes(1)->getInt(),
                $parser->readBytes(32)->serialize('int'),
                $parser->readBytes(32)->serialize('int')
            ];

            $recoveryFlags = $this->math->sub($byte, 27);
            if ($recoveryFlags < 0 || $recoveryFlags > 7) {
                throw new \InvalidArgumentException('invalid signature type');
            }

            $isCompressed = ($recoveryFlags & 4) != 0;

        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full signature from parser');
        }

        $signature = new CompactSignature($r, $s, $recoveryFlags, $isCompressed);
        return $signature;
    }

    /**
     * @param $string
     * @return CompactSignature
     * @throws ParserOutOfRange
     */
    public function parse($string)
    {
        $parser = new Parser($string);
        $signature = $this->fromParser($parser);
        return $signature;
    }
}
