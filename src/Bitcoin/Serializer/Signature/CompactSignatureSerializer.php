<?php

namespace BitWasp\Bitcoin\Serializer\Signature;

use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
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
        $parser = new Parser;
        $parser
            ->writeInt(1, $signature->getFlags())
            ->writeBytes(32, $this->math->decHex($signature->getR()))
            ->writeBytes(32, $this->math->decHex($signature->getS()));

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

            $isCompressed = ($this->math->bitwiseAnd($recoveryFlags, 4) != 0);
            $recoveryId = $recoveryFlags - ($isCompressed ? 4 : 0);
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full signature from parser');
        }

        $signature = new CompactSignature($r, $s, $recoveryId, $isCompressed);
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
