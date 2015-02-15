<?php

namespace Bitcoin\Crypto\Random;

/**
 * Interface DRBGInterface
 * @package Bitcoin\Crypto\DRBG
 * @author Thomas Kerin
 */
interface RbgInterface
{
    /**
     * Return $numBytes bytes deterministically derived from a seed
     *
     * @param int $numNumBytes
     * @return \Bitcoin\Buffer
     */
    public function bytes($numNumBytes);
}
