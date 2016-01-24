<?php

namespace BitWasp\Bitcoin\Network;

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
     * @return string
     */
    public function getP2WPKHByte();

    /**
     * Get the private key byte for the network
     *
     * @return string
     */
    public function getPrivByte();

    /**
     * Check if the network is testnet
     *
     * @return bool
     */
    public function isTestnet();

    /**
     * Return the HD public bytes for this network
     *
     * @return string
     */
    public function getHDPubByte();

    /**
     * Return the HD private bytes for this network
     *
     * @return string
     */
    public function getHDPrivByte();

    /**
     * @return string
     */
    public function getNetMagicBytes();
}
