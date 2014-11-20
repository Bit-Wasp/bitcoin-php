<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 20/11/14
 * Time: 08:55
 */

namespace Bitcoin;


interface KeyInterface {
    /**
     * Check if the key should be be using compressed format
     * @return mixed
     */
    public function isCompressed();

    /**
     * Return the hex / buffer of this key.
     * @return mixed
     */
    public function getHex();
} 