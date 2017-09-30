<?php

namespace BitWasp\Bitcoin\RpcTest;

use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use Nbobtc\Command\Command;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $rpcHost;

    /**
     * @var string
     */
    protected $rpcPort;

    /**
     * @var string
     */
    protected $rpcUser;

    /**
     * @var string
     */
    protected $rpcPass;

    /**
     * @var \Nbobtc\Http\Client
     */
    protected $client;

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
        $this->initRpcConfig();
        parent::__construct($name, $data, $dataName);
    }

    protected function initRpcConfig()
    {
        if (!($host = getenv("RPC_TEST_HOST"))) {
            throw new \RuntimeException("Missing RPC_TEST_HOST from environment");
        }
        if (!($port = getenv("RPC_TEST_PORT"))) {
            throw new \RuntimeException("Missing RPC_TEST_PORT from environment");
        }
        if (!($user = getenv("RPC_TEST_USERNAME"))) {
            throw new \RuntimeException("Missing RPC_TEST_USERNAME from environment");
        }
        if (!($pass = getenv("RPC_TEST_PASSWORD"))) {
            throw new \RuntimeException("Missing RPC_TEST_PASSWORD from environment");
        }
        if (!($networkName = getenv("RPC_NETWORK"))) {
            throw new \RuntimeException("Missing RPC_NETWORK from environment");
        }

        if ($networkName === "bitcoin") {
            $this->network = NetworkFactory::bitcoin();
        } else if ($networkName === "bitcoinTestnet") {
            $this->network = NetworkFactory::bitcoinTestnet();
        } else {
            throw new \RuntimeException("Unconfigured network");
        }

        $this->rpcHost = $host;
        $this->rpcPort = $port;
        $this->rpcUser = $user;
        $this->rpcPass = $pass;
    }

    /**
     * @return string
     */
    protected function getRpcDsn()
    {
        return "http://{$this->rpcUser}:{$this->rpcPass}@{$this->rpcHost}:{$this->rpcPort}";
    }

    /**
     * @return \Nbobtc\Http\Client
     */
    protected function getRpcClient()
    {
        if (null === $this->client) {
            $this->client = new \Nbobtc\Http\Client($this->getRpcDsn());
        }

        return $this->client;
    }

    /**
     * @param string $command
     * @param array $params
     * @return mixed
     */
    protected function makeRpcRequest($command, array $params = [])
    {
        $unsorted = $this->getRpcClient()->sendCommand(new Command($command, $params));
        $jsonResult = $unsorted->getBody()->getContents();
        $json = json_decode($jsonResult, true);
        if (false === $json) {
            throw new \RuntimeException("Invalid JSON from server");
        }
        return $json;
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
     * @param string $file
     * @return string
     */
    public function dataPath($file)
    {
        return __DIR__ . '/../tests/Data/' . $file;
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

}
