<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

interface PublicKeyInterface extends KeyInterface
{
    /**
     * Length of an uncompressed key
     */
    const LENGTH_UNCOMPRESSED = 65;

    /**
     * Length of a compressed key
     */
    const LENGTH_COMPRESSED = 33;

    /**
     * When key is uncompressed, this is the prefix.
     */
    const KEY_UNCOMPRESSED = '04';

    /**
     * When y coordinate is even, prepend x coordinate with this hex byte
     */
    const KEY_COMPRESSED_EVEN = '02';

    /**
     * When y coordinate is odd, prepend x coordinate this this hex byte
     */
    const KEY_COMPRESSED_ODD = '03';

    /**
     * @param BufferInterface $msg32
     * @param SignatureInterface $signature
     * @return bool
     */
    public function verify(BufferInterface $msg32, SignatureInterface $signature);
}
