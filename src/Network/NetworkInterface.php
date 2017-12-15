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
     * Return the string that binds address signed messages to
     * this network
     *
     * @return string
     */
    public function getSignedMessageMagic();

    /**
     * Returns the segwit bech32 prefix
     *
     * @return string
     */
    public function getSegwitBech32Prefix();

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
