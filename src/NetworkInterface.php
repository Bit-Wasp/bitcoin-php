<?php

namespace Bitcoin;

/**
 * Interface NetworkInterface
 * @package Bitcoin
 */
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
     *
     * @return mixed
     */
    public function isTestnet();

    /**
     * Return the HD public bytes for this network
     *
     * @return mixed
     */
    public function getHDPubByte();

    /**
     * Return the HD private bytes for this network
     *
     * @return mixed
     */
    public function getHDPrivByte();
}
