<?php

namespace Bitcoin;

/**
 * Interface PrivateKeyInterface
 * @package Bitcoin
 */
interface PrivateKeyInterface
{
    /**
     * @param NetworkInterface $network
     * @return mixed
     */
    public function getWif(NetworkInterface $network);
}
