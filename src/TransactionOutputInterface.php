<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/11/14
 * Time: 16:00
 */

namespace Bitcoin;


interface TransactionOutputInterface {
    public function getScript();
    public function getValue();
    public function serialize();
} 