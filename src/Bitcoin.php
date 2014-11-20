<?php
namespace Bitcoin;

class Bitcoin
{
    /**
     * @return \Mdanter\Ecc\MathAdapter
     */
    public static function getMath()
    {
        return \Mdanter\Ecc\EccFactory::getAdapter();
    }

    /**
     * @return \Mdanter\Ecc\NumberTheory
     */
    public static function getNumberTheory()
    {
        return \Mdanter\Ecc\EccFactory::getNumberTheory();
    }

}
