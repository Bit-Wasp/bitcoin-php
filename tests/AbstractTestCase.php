<?php

namespace BitWasp\Bitcoin\Tests;

use BitWasp\Bitcoin\Block\BlockFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Math\Math;
use Mdanter\Ecc\EccFactory;
use \BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter as PhpEccAdapter;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{

    /**
     * @var resource
     */
    private static $secp256k1Context;

    /**
     * @return resource
     */
    public static function getSecp256k1Context()
    {
        if (null === self::$secp256k1Context) {
            self::$secp256k1Context = secp256k1_context_create(SECP256K1_CONTEXT_VERIFY | SECP256K1_CONTEXT_SIGN);
        }

        return self::$secp256k1Context;
    }

    /**
     * @param string $file
     * @return string
     */
    public function dataPath($file)
    {
        return __DIR__ . '/Data/' . $file;
    }

    /**
     * @param string $filename
     * @return string
     */
    public function dataFile($filename)
    {
        return file_get_contents($this->dataPath($filename));
    }

    /**
     * @return array
     */
    public function getBlocks()
    {
        $blocks = $this->dataFile('180blocks');
        $a = explode("\n", $blocks);
        return array_filter($a, 'strlen');
    }

    /**
     * @param $i
     * @return \BitWasp\Bitcoin\Block\BlockInterface
     */
    public function getBlock($i)
    {
        $blocks = $this->getBlocks();
        return BlockFactory::fromHex($blocks[$i]);
    }

    /**
     * @return \BitWasp\Bitcoin\Block\Block
     */
    public function getGenesisBlock()
    {
        return $this->getBlock(0);
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
            if (getenv('EXT_SECP256K1') == false || getenv('EXT_SECP256K1') == '') {
                $adapters[] = [EcAdapterFactory::getPhpEcc($math, $generator)];
            } else {
                $adapters[] = [EcAdapterFactory::getSecp256k1($math, $generator)];
            }
        } else {
            // Env var was set, just pass secp256k1
            $adapters[] = [(extension_loaded('secp256k1')
                ? EcAdapterFactory::getSecp256k1($math, $generator)
                : EcAdapterFactory::getPhpEcc($math, $generator))];
        }

        return $adapters;
    }

    /**
     * @param $flagStr
     * @return int
     */
    public function getInterpreterFlags($flagStr)
    {
        $array = explode(",", $flagStr);
        $int = 0;
        foreach ($array as $activeFlag) {
            $f = constant('\BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface::'.$activeFlag);
            $int |= $f;
        }

        return $int;
    }

    /**
     * @return Math
     */
    public function safeMath()
    {
        return new Math();
    }

    /**
     * @return \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    public function safeGenerator()
    {
        return EccFactory::getSecgCurves($this->safeMath())->generator256k1();
    }

    /**
     * @return PhpEccAdapter|\BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter
     */
    public function safeEcAdapter()
    {
        $math = $this->safeMath();
        $generator = $this->safeGenerator();
        return extension_loaded('secp256k1') ? EcAdapterFactory::getSecp256k1($math, $generator): new PhpEccAdapter($math, $generator);
    }
}
