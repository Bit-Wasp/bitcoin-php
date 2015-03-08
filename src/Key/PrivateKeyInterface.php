<?php

namespace Afk11\Bitcoin\Key;

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
