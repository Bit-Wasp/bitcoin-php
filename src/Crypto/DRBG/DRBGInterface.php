<?php

namespace Bitcoin\Crypto\DRBG;

use Bitcoin\Buffer;

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
     * @return \Bitcoin\Buffer
     */
    public function bytes($numNumBytes);
}
