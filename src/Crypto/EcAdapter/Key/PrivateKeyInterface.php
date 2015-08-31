<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Key;

use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;

interface PrivateKeyInterface extends KeyInterface
{
    /**
     * Return the decimal secret multiplier
     *
     * @return int|string
     */
    public function getSecretMultiplier();

    /**
     * @param Buffer $msg32
     * @param RbgInterface $rbg
     * @return SignatureInterface
     */
    public function sign(Buffer $msg32, RbgInterface $rbg = null);

    /**
     * Return the public key.
     *
     * @return PublicKeyInterface
     */
    public function getPublicKey();

    /**
     * Convert the private key to wallet import format. This function
     * optionally takes a NetworkInterface for exporting keys for other networks.
     *
     * @param NetworkInterface $network
     * @return string
     */
    public function toWif(NetworkInterface $network = null);
}
