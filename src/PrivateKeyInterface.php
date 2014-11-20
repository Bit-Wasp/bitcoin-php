<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 20/11/14
 * Time: 06:31
 */

namespace Bitcoin;

interface PrivateKeyInterface
{

    /**
     * Get the decimal private key
     */
    public function getDec();

    /**
     * Get the hex private key
     */
    public function getHex();

    /**
     * @param NetworkInterface $network
     * @return mixed
     */
    public function getWif(NetworkInterface $network);
} 