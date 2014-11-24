<?php

namespace Bitcoin;

/**
 * Interface TransactionOutputInterface
 * @package Bitcoin
 */
interface TransactionOutputInterface
{
    public function getScript();
    public function getValue();
    public function serialize();
}
