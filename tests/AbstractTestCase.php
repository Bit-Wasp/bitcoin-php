<?php

namespace BitWasp\Bitcoin\Tests;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\PhpEcc;
use BitWasp\Bitcoin\Crypto\EcAdapter\Secp256k1;

class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function getEcAdapters()
    {
        $math = Bitcoin::getMath();
        $generator = Bitcoin::getGenerator();

        $adapters = array(
            [new Secp256k1($math, $generator)],
            [new PhpEcc($math, $generator)]
        );
        return $adapters;
    }
}