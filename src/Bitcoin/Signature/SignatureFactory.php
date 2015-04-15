<?php

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializer\Signature\DerSignatureSerializer;

class SignatureFactory
{

    /**
     * @param $string
     * @param Math $math
     * @return Signature
     */
    public static function fromHex($string, Math $math = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $serializer = new DerSignatureSerializer($math);
        $signature = $serializer->parse($string);
        return $signature;
    }
}
