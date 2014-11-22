<?php

namespace Bitcoin;

/**
 * Class NumberTheory
 * @package Bitcoin
 */
class NumberTheory
{
    /**
     * @var \Mdanter\Ecc\NumberTheory
     */
    private static $adapter = null;

    /**
     * Set the adapter to use
     *
     * @param \Mdanter\Ecc\NumberTheory $theory
     */
    public static function setAdapter(\Mdanter\Ecc\NumberTheory $theory)
    {
        self::$adapter = $theory;
    }

    /**
     * Statically call the adapter (or catch the default) for number theory functions
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (is_null(static::$adapter)) {
            static::$adapter = Bitcoin::getNumberTheory();
        }

        return call_user_func_array(array(static::$adapter, $name), $arguments);
    }
} 