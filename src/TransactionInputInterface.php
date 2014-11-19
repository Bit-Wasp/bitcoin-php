<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/11/14
 * Time: 15:57
 */

namespace Bitcoin;

interface TransactionInputInterface {

    const DEFAULT_SEQUENCE = 0xffffffff;
    
    public function getTransactionId();
    public function getVout();
    public function getSequence();
    public function getScript();
    public function isCoinBase();
    public function serialize();
} 