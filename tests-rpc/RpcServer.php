<?php

namespace BitWasp\Bitcoin\RpcTest;


use BitWasp\Bitcoin\Network\NetworkInterface;
use Nbobtc\Command\Command;
use Nbobtc\Http\Client;

class RpcServer
{
    /**
     * @var string
     */
    private $dataDir;

    /**
     * @var string
     */
    private $bitcoind;

    /**
     * @var NetworkInterface
     */
    private $network;

    /**
     * @var RpcCredential
     */
    private $credential;

    /**
     * @var Client
     */
    private $client;

    /**
     * RpcServer constructor.
     * @param $bitcoind
     * @param $dataDir
     * @param NetworkInterface $network
     * @param RpcCredential $credential
     */
    public function __construct($bitcoind, $dataDir, NetworkInterface $network, RpcCredential $credential)
    {
        $this->bitcoind = $bitcoind;
        $this->dataDir = $dataDir;
        $this->network = $network;
        $this->credential = $credential;
    }

    /**
     * @return string
     */
    private function getPidFile()
    {
        return "{$this->dataDir}/regtest/bitcoind.pid";
    }

    /**
     * @return string
     */
    private function getConfigFile()
    {
        return "{$this->dataDir}/bitcoin.conf";
    }

    /**
     * @param RpcCredential $rpcCredential
     */
    private function writeConfigToFile(RpcCredential $rpcCredential)
    {
        $fd = fopen($this->getConfigFile(), "w");
        if (!$fd) {
            throw new \RuntimeException("Failed to open bitcoin.conf for writing");
        }
        if (!fwrite($fd, $rpcCredential->getConfig())) {
            throw new \RuntimeException("Failed to write to bitcoin.conf");
        }
        fclose($fd);
    }

    /**
     * @return void
     */
    public function start()
    {
        if ($this->isRunning()) {
            return;
        }

        $this->writeConfigToFile($this->credential);
        $res = 0;
        $out = '';
        $result = exec(sprintf("%s -datadir=%s", $this->bitcoind, $this->dataDir), $out, $res);

        if ($res !== 0) {
            throw new \RuntimeException("Failed to start bitcoind: {$this->dataDir}\n");
        }

        $start = microtime(true);
        $limit = 10;
        $connected = false;

        $conn = $this->getClient();
        do {
            try {
                $result = json_decode($conn->sendCommand(new Command("getblockchaininfo"))->getBody()->getContents(), true);
                if ($result['error'] === null) {
                    $connected = true;
                } else {
                    if ($result['error']['code'] !== -28) {
                        throw new \RuntimeException("Unexpected error code during startup");
                    }

                    sleep(0.2);
                }

            } catch (\Exception $e) {
                sleep(0.2);
            }

            if (microtime(true) > $start + $limit) {
                throw new \RuntimeException("Timeout elapsed, never made connection to bitcoind");
            }
        } while (!$connected);
    }

    private function getClient() {
        $client = new \Nbobtc\Http\Client($this->credential->getDsn());
        $client->withDriver(new CurlDriver());
        return $client;
    }

    /**
     * @param string $src
     */
    private function recursiveDelete($src)
    {
        $dir = opendir($src);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $full = $src . '/' . $file;
                if ( is_dir($full) ) {
                    $this->recursiveDelete($full);
                }
                else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }

    /**
     * @return void
     */
    public function destroy()
    {
        if ($this->isRunning()) {
            $this->request("stop");

            do {
                sleep(0.2);
            } while($this->isRunning());

            $this->recursiveDelete($this->dataDir);

        }
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        return file_exists($this->getPidFile());
    }

    /**
     * @return Client
     */
    public function makeClient()
    {
        if (!$this->isRunning()) {
            throw new \RuntimeException("No client, server not running");
        }

        if (null === $this->client) {
            $this->client = $this->getClient();
        }

        return $this->client;
    }

    /**
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public function request($method, array $params = [])
    {
        $unsorted = $this->makeClient()->sendCommand(new Command($method, $params));
        $jsonResult = $unsorted->getBody()->getContents();
        $json = json_decode($jsonResult, true);
        if (false === $json) {
            throw new \RuntimeException("Invalid JSON from server");
        }
        return $json;
    }

    /**
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public function makeRpcRequest($method, $params = [])
    {
        return $this->request($method, $params);
    }

}
