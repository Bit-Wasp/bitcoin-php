<?php

namespace Bitcoin;

/**
 * Interface PublicKeyInterface
 * @package Bitcoin
 */
interface PublicKeyInterface
{
    /**
     * Length of an uncompressed key
     */
    const LENGTH_UNCOMPRESSED = 130;

    /**
     * When key is uncompressed, this is the prefix.
     */
    const KEY_UNCOMPRESSED = '04';

    /**
     * Length of a compressed key
     */
    const LENGTH_COMPRESSED = 66;
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
     * Get the X coordinate in decimal
     *
     * @return mixed
     */
    public function getX();

    /**
     * Get the Y coordinate in decimal
     *
     * @return mixed
     */
    public function getY();

    /**
     * Get the curve for the point
     *
     * @return mixed
     */
    public function getCurve();

    /**
     * Get public key point on the curve
     *
     * @return mixed
     */
    public function getPoint();

    /**
     * Verify that this public key produced the given $signature for the message $hash
     *
     * @param Buffer $hash
     * @param SignatureInterface $signature
     * @return bool
     */
    public function verify(Buffer $hash, SignatureInterface $signature);
}
