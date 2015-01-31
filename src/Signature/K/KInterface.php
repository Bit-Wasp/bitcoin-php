<?php

namespace Bitcoin\Signature\K;

/**
 * Interface KInterface
 * @package Bitcoin\Signature\K
 * @author Thomas Kerin
 */
interface KInterface
{
    /**
     * @return \Bitcoin\Buffer
     */
    public function getK();
}
