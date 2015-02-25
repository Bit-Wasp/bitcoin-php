<?php

namespace Afk11\Bitcoin\Key;

use Afk11\Bitcoin\NetworkInterface;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Signature\K\KInterface;

interface PrivateKeyInterface extends KeyInterface
{

    /**
     * Return the decimal secret multiplier
     *
     * @return int|string
     */
    public function getSecretMultiplier();

    /**
     * @return PublicKeyInterface
     */
    public function getPublicKey();
}
