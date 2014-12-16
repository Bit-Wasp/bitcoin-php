<?php

namespace Bitcoin\Key;

use Bitcoin\NetworkInterface;
use Bitcoin\Util\Buffer;
use Bitcoin\Signature\K\KInterface;

/**
 * Interface PrivateKeyInterface
 * @package Bitcoin
 * @author Thomas Kerin
 */
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
     * Sign a buffer (hash of a message, and optionally accept a source
     * for the K value (which can be random or deterministic)
     *
     * @param Buffer $messageHash
     * @param KInterface $kProvider
     * @return mixed
     */
    public function sign(Buffer $messageHash, KInterface $kProvider = null);
}
