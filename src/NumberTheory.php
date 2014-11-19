<?php

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
} 