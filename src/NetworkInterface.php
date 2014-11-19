<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/11/14
 * Time: 16:09
 */

namespace Bitcoin;


interface NetworkInterface {
    public function getAddressByte();
    public function getP2shByte();
    public function getPrivByte();
    public function isTestnet();
} 