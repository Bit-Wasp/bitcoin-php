<?php

namespace Bitcoin;

/**
 * Interface SignatureInterface
 * @package Bitcoin
 * @author  Thomas Kerin
 */
interface SignatureInterface
{
    public function getR();

    public function getS();
}
