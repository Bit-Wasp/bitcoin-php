<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;

interface DerSignatureSerializerInterface
{
    /**
     * @param SignatureInterface $signature
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(SignatureInterface $signature);

    /**
     * @param string|\BitWasp\Buffertools\Buffer $data
     * @return SignatureInterface
     */
    public function parse($data);
}
