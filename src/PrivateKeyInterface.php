<?php

namespace Bitcoin;

use Bitcoin\Util\Buffer;

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
     * @param Buffer $hash
     * @param SignatureKInterface $kProvider
     * @return mixed
     */
    public function sign(Buffer $hash, SignatureKInterface $kProvider = null);
}
