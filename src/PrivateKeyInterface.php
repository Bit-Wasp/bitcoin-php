<?php

namespace Bitcoin;

use Bitcoin\Util\Buffer;

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

    public function sign(Buffer $hash, SignatureKInterface $kProvider = null);

}
