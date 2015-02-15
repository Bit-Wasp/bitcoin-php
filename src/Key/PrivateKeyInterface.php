<?php

namespace Afk11\Bitcoin\Key;

use Afk11\Bitcoin\NetworkInterface;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Signature\K\KInterface;

interface PrivateKeyInterface
{
    /**
     * Return the WIF key
     *
     * @param NetworkInterface $network
     * @return mixed
     */
    public function getWif(NetworkInterface $network);

    /**
     * Return the decimal secret multiplier
     *
     * @return int|string
     */
    public function getSecretMultiplier();
}
