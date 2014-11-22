<?php

namespace Bitcoin;

/**
 * Interface TransactionInterface
 * @package Bitcoin
 */
interface TransactionInterface {
    const MAX_VERSION  = 4294967296;
    const MAX_LOCKTIME = 4294967296;

    public function getTransactionId();
    public function getVersion();
    public function getInput($index);
    public function getInputs();
    public function getOutput($index);
    public function getOutputs();
    public function getLockTime();
    public function serialize();
    public function getNetwork();

//    public static function fromHex() {

    //}

} 