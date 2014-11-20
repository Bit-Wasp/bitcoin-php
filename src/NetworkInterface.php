<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/11/14
 * Time: 16:09
 */

namespace Bitcoin;


interface NetworkInterface
{
    /**
     * Return a byte for the networks regular version
     *
     * @return string
     */
    public function getAddressByte();

    /**
     * Return the p2sh byte for the network
     *
     * @return string
     */
    public function getP2shByte();

    /**
     * Get the private key byte for the network
     *
     * @return string
     */
    public function getPrivByte();

    /**
     * Check if the network is testnet
     * @return mixed
     */
    public function isTestnet();
} 