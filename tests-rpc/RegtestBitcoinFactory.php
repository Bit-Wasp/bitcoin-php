<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\RpcTest;

use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\NetworkFactory;

class RegtestBitcoinFactory
{
    const TESTS_DIR = "BITCOIND_TEST_DIR";
    const BITCOIND = "BITCOIND_PATH";

    /**
     * @var array|false|null|string
     */
    private $testsDirPath;

    /**
     * @var array|false|null|string
     */
    private $bitcoindPath;

    /**
     * @var string[]
     */
    private $testDir = [];

    /**
     * @var RpcServer[]
     */
    private $server = [];

    /**
     * @var \BitWasp\Bitcoin\Network\NetworkInterface
     */
    private $network;

    /**
     * @var RpcCredential
     */
    private $credential;

    public function __construct()
    {
        $this->testsDirPath = $this->envOrDefault("BITCOIND_TEST_DIR", "/tmp");
        $this->bitcoindPath = $this->envOrDefault("BITCOIND_PATH");
        if (null === $this->bitcoindPath) {
            throw new \RuntimeException("Missing BITCOIND_PATH variable");
        }

        $this->network = NetworkFactory::bitcoinTestnet();
        $this->credential = new RpcCredential("127.0.0.1", 18332, "rpcuser", "rpcpass", false);
    }

    /**
     * @param string $var
     * @param string|null $default
     * @return string
     */
    private function envOrDefault(string $var, string $default = null): string
    {
        $value = getenv($var);
        if (in_array($value, [null, false, ""])) {
            $value = $default;
        }
        return $value;
    }

    /**
     * @return string
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    protected function createRandomTestDir(): string
    {
        $this->testDir[] = $dir = $this->testsDirPath . "/" . (new Random())->bytes(5)->getHex();
        if (!mkdir($dir)) {
            throw new \RuntimeException("Failed to create test dir!");
        }
        return $dir;
    }

    /**
     * @param array $options
     * @return RpcServer
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public function startBitcoind($options = []): RpcServer
    {
        $testDir = $this->createRandomTestDir();
        $rpcServer = new RpcServer($this->bitcoindPath, $testDir, $this->network, $this->credential, $options);
        $rpcServer->start();
        $this->server[] = $rpcServer;
        return $rpcServer;
    }

    /**
     *
     */
    protected function cleanup()
    {
        $servers = 0;
        $dirs = 0;
        foreach ($this->server as $server) {
            if ($server->isRunning()) {
                $servers++;
                $server->destroy();
            }
        }

        echo "Cleaned up {$servers} servers, and {$dirs} directories\n";
    }

    public function __destruct()
    {
        $this->cleanup();
    }
}
