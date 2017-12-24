<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\RpcTest;

use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $scriptFlagNames;

    /**
     * @var NetworkInterface
     */
    protected $network;

    /**
     * AbstractTestCase constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    /**
     * @param string $name
     * @return array
     */
    public function jsonDataFile(string $name): array
    {
        $contents = $this->dataFile($name);
        $decoded = json_decode($contents, true);
        if (false === $decoded || json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON file ' . $name);
        }

        return $decoded;
    }

    /**
     * @param string $filename
     * @return string
     */
    public function dataFile(string $filename): string
    {
        $contents = file_get_contents($this->dataPath($filename));
        if (false === $contents) {
            throw new \RuntimeException('Failed to data file ' . $filename);
        }
        return $contents;
    }

    /**
     * @param string $file
     * @return string
     */
    public function dataPath(string $file): string
    {
        return __DIR__ . '/../tests/Data/' . $file;
    }

    /**
     * @return array
     */
    public function calcMapScriptFlags(): array
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
    public function getScriptFlagsFromString(string $string): int
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
}
