<?php

namespace Bitcoin;

/**
 * Interface TransactionInputInterface
 * @package Bitcoin
 */
interface TransactionInputInterface
{

    const DEFAULT_SEQUENCE = 0xffffffff;
    
    public function getTransactionId();
    public function getVout();
    public function getSequence();
    public function getScript();
    public function isCoinBase();
    public function serialize();
} 