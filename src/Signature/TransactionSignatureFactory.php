<?php

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializer\Signature\DerSignatureSerializer;
use BitWasp\Bitcoin\Serializer\Signature\TransactionSignatureSerializer;

class TransactionSignatureFactory
{

    /**
     * @param $string
     * @param Math $math
     * @return TransactionSignatureInterface
     */
    public static function fromHex($string, Math $math = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $serializer = new TransactionSignatureSerializer(new DerSignatureSerializer($math));
        $signature = $serializer->parse($string);
        return $signature;
    }
}
