<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 20/11/14
 * Time: 06:54
 */

namespace Bitcoin;


interface PublicKeyInterface
{

    /**
     * When y coordinate is even, prepend x coordinate with this if
     * generating a public key
     */
    const PARITYBYTE_EVEN = '02';

    /**
     * When y coordinate is odd, prepend x coordinate with this if
     * generating a public key
     */
    const PARITYBYTE_ODD = '03';

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

} 