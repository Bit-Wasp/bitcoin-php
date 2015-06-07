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
            // Env var was set, just pass secp256k1
            $adapters[] = [(extension_loaded('secp256k1') ? new Secp256k1($math, $generator) : new PhpEcc($math, $generator))];
        }

        return $adapters;
    }

    public function safeMath()
    {
        return new Math();
    }

    public function safeGenerator()
    {
        return EccFactory::getSecgCurves($this->safeMath())->generator256k1();
    }

    public function safeEcAdapter()
    {
        $math = $this->safeMath();
        $generator = $this->safeGenerator();
        return extension_loaded('secp256k1') ? new Secp256k1($math, $generator) : new PhpEcc($math, $generator);
    }
}
