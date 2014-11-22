<?php

namespace Bitcoin;

/**
 * Interface PrivateKeyInterface
 * @package Bitcoin
 */
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