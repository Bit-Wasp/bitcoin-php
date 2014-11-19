<?php

namespace Bitcoin;

class Bitcoin
{
    public static function getMath()
    {
        return \Mdanter\Ecc\EccFactory::getAdapter();
    }

    public static function getNumberTheory()
    {
        return \Mdanter\Ecc\EccFactory::getNumberTheory();
    }

    public static function int()
    {

    }
}