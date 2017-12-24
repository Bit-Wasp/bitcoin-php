<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests;

use BitWasp\Bitcoin\Block\BlockFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter as PhpEccAdapter;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use Mdanter\Ecc\EccFactory;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $scriptFlagNames;

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
     * @param callable|\Closure $closure
     * @param string $error - exception FQDN
     * @param null $errorMessage - optional, assert exception matches this error message
     */
    public function assertThrows($closure, $error, $errorMessage = null)
    {
        $err = null;
        try {
            $closure();
        } catch (\Exception $e) {
            $err = $e;
        }

        $this->assertInstanceOf($error, $err, 'should have thrown exception ' . $error);

        if (is_string($errorMessage)) {
            $this->assertEquals($errorMessage, $err->getMessage());
        }
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
        $contents = file_get_contents($this->dataPath($filename));
        if (false === $contents) {
            throw new \RuntimeException('Failed to data file ' . $filename);
        }
        return $contents;
    }

    /**
     * @param string $name
     * @return array
     */
    public function jsonDataFile($name)
    {
        $contents = $this->dataFile($name);
        $decoded = json_decode($contents, true);
        if (false === $decoded || json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON file ' . $name);
        }

        return $decoded;
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
        $adapters[] = [EcAdapterFactory::getPhpEcc($math, $generator)];
        if (extension_loaded('secp256k1')) {
            $adapters[] = [EcAdapterFactory::getSecp256k1($math, $generator)];
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
     * @return array
     */
    public function calcMapScriptFlags()
    {
        if (null === $this->scriptFlagNames) {
            $this->scriptFlagNames = [
                "NONE" => Interpreter::VERIFY_NONE,
                "P2SH" => Interpreter::VERIFY_P2SH,
                "STRICTENC" => Interpreter::VERIFY_STRICTENC,
                "DERSIG" => Interpreter::VERIFY_DERSIG,
                "LOW_S" => Interpreter::VERIFY_LOW_S,
                "SIGPUSHONLY" => Interpreter::VERIFY_SIGPUSHONLY,
                "MINIMALDATA" => Interpreter::VERIFY_MINIMALDATA,
                "NULLDUMMY" => Interpreter::VERIFY_NULL_DUMMY,
                "DISCOURAGE_UPGRADABLE_NOPS" => Interpreter::VERIFY_DISCOURAGE_UPGRADABLE_NOPS,
                "CLEANSTACK" => Interpreter::VERIFY_CLEAN_STACK,
                "CHECKLOCKTIMEVERIFY" => Interpreter::VERIFY_CHECKLOCKTIMEVERIFY,
                "CHECKSEQUENCEVERIFY" => Interpreter::VERIFY_CHECKSEQUENCEVERIFY,
                "WITNESS" => Interpreter::VERIFY_WITNESS,
                "DISCOURAGE_UPGRADABLE_WITNESS_PROGRAM" => Interpreter::VERIFY_DISCOURAGE_UPGRADABLE_WITNESS_PROGRAM,
                "MINIMALIF" => Interpreter::VERIFY_MINIMALIF,
                "NULLFAIL" => Interpreter::VERIFY_NULLFAIL,
            ];
        }

        return $this->scriptFlagNames;
    }

    /**
     * @param string $string
     * @return int
     */
    public function getScriptFlagsFromString($string)
    {
        $mapFlagNames = $this->calcMapScriptFlags();
        if (strlen($string) === 0) {
            return Interpreter::VERIFY_NONE;
        }

        $flags = 0;
        $words = explode(",", $string);
        foreach ($words as $word) {
            if (!isset($mapFlagNames[$word])) {
                throw new \RuntimeException('Unknown verification flag: ' . $word);
            }

            $flags |= $mapFlagNames[$word];
        }

        return $flags;
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
