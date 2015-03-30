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

        $adapters = [
            [new PhpEcc($math, $generator)]
        ];

        if (getenv('TRAVIS_PHP_VERSION') !== 'hhvm' && extension_loaded('secp256k1')) {
            $adapters[] = [new Secp256k1($math, $generator)];
        }

        return $adapters;
    }
}