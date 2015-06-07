<?php

namespace BitWasp\Bitcoin\Tests;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Crypto\EcAdapter\PhpEcc;
use BitWasp\Bitcoin\Crypto\EcAdapter\Secp256k1;
use Mdanter\Ecc\EccFactory;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{

    public function dataFile($filename)
    {
        return file_get_contents(__DIR__ . '/Data/' . $filename);
    }

    /**
     * @return array
     */
    public function getEcAdapters()
    {
        $math = new Math;
        $generator = EccFactory::getSecgCurves()->generator256k1();
        $adapters = [];

        if (getenv('TRAVIS_PHP_VERSION')) {
            // If travis
            // If EXT_SECP256K1 env var is set, only return secp256k1.
            // Otherwise return phpecc
            if (strlen(getenv('EXT_SECP256K1')) == 0) {
                $adapters[] = [new PhpEcc($math, $generator)];
            } else {
                $adapters[] = [new Secp256k1($math, $generator)];
            }
        } else {
            // If NOT travis
            // Return both adapters if EXT_SECP256K1 is not set (secp256k1 only returned if ext is found)
            if (strlen(getenv('EXT_SECP256K1')) == 0) {
                $adapters[] = [new PhpEcc($math, $generator)];

                if (extension_loaded('secp256k1')) {
                    $adapters[] = [new Secp256k1($math, $generator)];
                }
            } else {
                // Env var was set, just pass secp256k1
                $adapters[] = [new Secp256k1($math, $generator)];
            }
        }

        return $adapters;
    }
}
