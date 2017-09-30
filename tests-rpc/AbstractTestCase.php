<?php

namespace BitWasp\Bitcoin\RpcTest;

use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
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
}
