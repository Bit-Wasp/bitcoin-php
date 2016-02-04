<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;

interface DerSignatureSerializerInterface
{
    /**
     * @return EcAdapterInterface
     */
    public function getEcAdapter();

    /**
     * @param SignatureInterface $signature
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function serialize(SignatureInterface $signature);

    /**
     * @param string|\BitWasp\Buffertools\BufferInterface $data
     * @return SignatureInterface
     */
    public function parse($data);
}
