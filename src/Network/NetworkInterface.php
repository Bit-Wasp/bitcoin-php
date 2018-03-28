<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Network;

interface NetworkInterface
{
    /**
     * Return a byte for the networks regular version
     *
     * @return string
     */
    public function getAddressByte(): string;

    /**
     * Return a address prefix length in bytes
     *
     * @return int
     */
    public function getAddressPrefixLength(): int;

    /**
     * Return the string that binds address signed messages to
     * this network
     *
     * @return string
     */
    public function getSignedMessageMagic(): string;

    /**
     * Returns the prefix for bech32 segwit addresses
     *
     * @return string
     */
    public function getSegwitBech32Prefix(): string;

    /**
     * Return the p2sh byte for the network
     *
     * @return string
     */
    public function getP2shByte(): string;

    /**
     * Return the p2sh prefix length in bytes for the network
     *
     * @return int
     */
    public function getP2shPrefixLength(): int;

    /**
     * Get the private key byte for the network
     *
     * @return string
     */
    public function getPrivByte(): string;

    /**
     * Return the HD public bytes for this network
     *
     * @return string
     */
    public function getHDPubByte(): string;

    /**
     * Return the HD private bytes for this network
     *
     * @return string
     */
    public function getHDPrivByte(): string;

    /**
     * Returns the magic bytes for P2P messages
     * @return string
     */
    public function getNetMagicBytes(): string;
}
