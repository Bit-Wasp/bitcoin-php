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
        $adapters = [];

        if (getenv('TRAVIS_PHP_VERSION')) {
            if (strlen(getenv('EXT_SECP256K1')) == 0) {
                $adapters[] = new PhpEcc($math, $generator);
            } else {
                $adapters[] = new Secp256k1($math, $generator);
            }
        } else {
            $adapters[] = new PhpEcc($math, $generator);

            if (extension_loaded('secp256k1')) {
                $adapters[] = [new Secp256k1($math, $generator)];
            }
        }

        return $adapters;
    }
}