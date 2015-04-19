<?php

namespace BitWasp\Bitcoin\Key;

interface PublicKeyInterface extends KeyInterface
{
    /**
     * Length of an uncompressed key
     */
    const LENGTH_UNCOMPRESSED = 65;

    /**
     * When key is uncompressed, this is the prefix.
     */
    const KEY_UNCOMPRESSED = '04';

    /**
     * Length of a compressed key
     */
    const LENGTH_COMPRESSED = 33;
    /**
     * When y coordinate is even, prepend x coordinate with this if
     * generating a public key
     */
    const KEY_COMPRESSED_EVEN = '02';

    /**
     * When y coordinate is odd, prepend x coordinate with this if
     * generating a public key
     */
    const KEY_COMPRESSED_ODD = '03';

    /**
     * Get public key point on the curve
     *
     * @return \Mdanter\Ecc\Primitives\PointInterface
     */
    public function getPoint();
}
