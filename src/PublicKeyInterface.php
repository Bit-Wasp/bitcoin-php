<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 20/11/14
 * Time: 06:54
 */

namespace Bitcoin;


interface PublicKeyInterface {

    public function getX();
    public function getY();
    public function getCurve();
    public function getPoint();
    public function getGenerator();

} 