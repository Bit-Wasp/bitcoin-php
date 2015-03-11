<?php

namespace Afk11\Bitcoin\Key;

use Mdanter\Ecc\PointInterface;
use Mdanter\Ecc\GeneratorPoint;

class Point implements PointInterface
{
    /**
     * @var \Mdanter\Ecc\PointInterface
     */
    protected $point;

    /**
     * Take X, Y, and a generator point, and we can get what we need!
     *
     * @param $x
     * @param $y
     * @param GeneratorPoint $generator
     */
    public function __construct(GeneratorPoint $generator, $x, $y)
    {
        $this->point = $generator->getCurve()->getPoint($x, $y, $generator->getOrder());

        return $this;
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
        return $this->point->getCurve();
    }

    /**
     * @return int|string
     */
    public function getOrder()
    {
        return $this->point->getOrder();
    }

    /**
     * @return int|string
     */
    public function getX()
    {
        return $this->point->getX();
    }

    /**
     * @return int|string
     */
    public function getY()
    {
        return $this->point->getY();
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

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\PointInterface::isInfinity()
     */
    public function isInfinity()
    {
        return false;
    }
}
