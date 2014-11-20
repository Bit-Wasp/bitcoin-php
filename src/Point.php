<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 20/11/14
 * Time: 07:00
 */

namespace Bitcoin;

use \Mdanter\Ecc\EccFactory;
use \Mdanter\Ecc\PointInterface;
use \Mdanter\Ecc\GeneratorPoint;

/** TODO: To be removed already? */
class Point implements PointInterface {

    protected $curve;
    protected $x;
    protected $y;
    protected $order;
    protected $math;

    public function __construct($x, $y, GeneratorPoint $generator = null)
    {
        if ($generator == null) {
            $generator = \Mdanter\Ecc\EccFactory::getSecgCurves()->generator256k1();
        }

        $math = \Mdanter\Ecc\EccFactory::getAdapter();

        $this->point = new \Mdanter\Ecc\Point($generator->getCurve(), $x, $y, $generator->getOrder(), $math);
    }

    public function __toString()
    {
        return $this->point->__toString();
    }

    public function getCurve()
    {
        return $this->curve;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getX()
    {
        return $this->x;
    }

    public function getY()
    {
        return $this->y;
    }

    public function cmp(PointInterface $other)
    {
        return $this->point->cmp($other);
    }

    public function equals(PointInterface $other)
    {
        return $this->point->cmp($other) == 0;
    }

    public function add(PointInterface $addend)
    {
        return $this->point->add($addend);
    }

    public function mul($multiplier)
    {
        return $this->point->mul($multiplier);
    }

    public function getDouble()
    {
        return $this->point->getDouble();
    }

} 