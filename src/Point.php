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

class Point implements PointInterface
{

    /**
     * @var
     */
    protected $curve;

    /**
     * @var
     */
    protected $x;

    /**
     * @var
     */
    protected $y;

    /**
     * @var
     */
    protected $order;

    /**
     * @var
     */
    protected $math;

    /**
     * Take X, Y, and a generator point, and we can get what we need!
     *
     * @param $x
     * @param $y
     * @param GeneratorPoint $generator
     */
    public function __construct($x, $y, GeneratorPoint $generator = null)
    {
        if ($generator == null) {
            $generator = \Mdanter\Ecc\EccFactory::getSecgCurves()->generator256k1();
        }

        $math = \Mdanter\Ecc\EccFactory::getAdapter();

        $this->point = new \Mdanter\Ecc\Point($generator->getCurve(), $x, $y, $generator->getOrder(), $math);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->point->__toString();
    }

    /**
     * @return \Mdanter\Ecc\CurveFpInterface
     */
    public function getCurve()
    {
        return $this->curve;
    }

    /**
     * @return int|string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return int|string
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @return int|string
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @inheritdoc
     */
    public function cmp(PointInterface $other)
    {
        return $this->point->cmp($other);
    }

    /**
     * @inheritdoc
     */
    public function equals(PointInterface $other)
    {
        return $this->point->cmp($other) == 0;
    }

    /**
     * @inheritdoc
     */
    public function add(PointInterface $addend)
    {
        return $this->point->add($addend);
    }

    /**
     * @inheritdoc
     */
    public function mul($multiplier)
    {
        return $this->point->mul($multiplier);
    }

    /**
     * @inheritdoc
     */
    public function getDouble()
    {
        return $this->point->getDouble();
    }

};