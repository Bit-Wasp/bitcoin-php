<?php

require __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;

function parseSighashFlags($bits)
{
    if (($bits & SigHash::ANYONECANPAY) != 0) {
        $bits ^= SigHash::ANYONECANPAY;
        $anyoneCanPay = true;
    } else {
        $anyoneCanPay = false;
    }

    $main = null;
    foreach ([[SigHash::ALL, 'ALL'], [SigHash::NONE, 'NONE'], [SigHash::SINGLE, 'SINGLE']] as $arr) {
        list ($sh, $str) = $arr;
        if ($bits == $sh) {
            $main = $str;
            break;
        }
    }

    return [
        'flag' => $main,
        'anyoneCanPay' => $anyoneCanPay,
    ];
}

foreach ([
        SigHash::ALL,    SigHash::ALL|SigHash::ANYONECANPAY,
        SigHash::NONE,   SigHash::NONE|SigHash::ANYONECANPAY,
        SigHash::SINGLE, SigHash::SINGLE|SigHash::ANYONECANPAY,
    ] as $flag) {
    var_dump(parseSighashFlags($flag));
}
