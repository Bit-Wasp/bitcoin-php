<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/11/14
 * Time: 18:31
 */

namespace Bitcoin;

class Math {

    private static $adapter = null;

    public static function setAdapter(\Mdanter\Ecc\MathAdapter $math)
    {
        self::$adapter = $math;
    }

    public static function __callStatic($name, $arguments)
    {
        if (is_null(static::$adapter)) {
            static::$adapter = Bitcoin::getMath();
        }

        return call_user_func_array(array(static::$adapter, $name), $arguments);
    }

    public static function div_qr($dividend, $divisor)
    {
        $div = Math::div($dividend, $divisor);
        $remainder = Math::sub($dividend, Math::mul($div, $divisor));
        return array($div, $remainder);
    }
}