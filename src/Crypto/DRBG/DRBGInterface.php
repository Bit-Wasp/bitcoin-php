<?php

namespace Bitcoin\Crypto\DRBG;

use Bitcoin\Util\Buffer;

/**
 * Interface DRBGInterface
 * @package Bitcoin\Crypto\DRBG
 * @author Thomas Kerin
 */
interface DRBGInterface
{
    /**
     * Return $numBytes bytes deterministically derived from a seed
     *
     * @param int $numNumBytes
     * @return Buffer
     */
    public function bytes($numNumBytes);
}
