<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 20/11/14
 * Time: 06:31
 */

namespace Bitcoin;

interface PrivateKeyInterface {

    public function getDec();

    public function getWif(NetworkInterface $network);
} 