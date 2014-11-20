<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 20/11/14
 * Time: 08:55
 */

namespace Bitcoin;


interface KeyInterface {
    public function isCompressed();

    public function getHex();
} 