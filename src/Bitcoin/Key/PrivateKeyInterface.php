<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Network\NetworkInterface;

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

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function toWif(NetworkInterface $network = null);
}
