<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/11/14
 * Time: 18:18
 */

namespace Bitcoin;


class Uint {

    protected $math;

    public function __construct($bitsize, $decimal)
    {
        if ( ! is_numeric($bitsize)) {
            throw \Exception('Bitsize must be a decimal number');
        }

        if ( ! is_numeric($decimal)) {
            throw \Exception('Number must be a decimal');
        }
    }

    public static function hex($bitsize, $hex)
    {

    }
} 