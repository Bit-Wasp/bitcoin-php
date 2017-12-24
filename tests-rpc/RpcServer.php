<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\RpcTest;

use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;
use Nbobtc\Command\Command;
use Nbobtc\Http\Client;

class RpcServer
{
    const ERROR_STARTUP = -28;
    const ERROR_TX_MEMPOOL_CONFLICT = -26;

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
     * @var bool
     */
    private $softforks = false;

    private $defaultOptions = [
        "daemon" => 1,
        "server" => 1,
        "regtest" => 1,
    ];

    private $options = [];

    /**
     * RpcServer constructor.
     * @param $bitcoind
     * @param $dataDir
     * @param NetworkInterface $network
     * @param RpcCredential $credential
     * @param array $options
     */
    public function __construct(string $bitcoind, string $dataDir, NetworkInterface $network, RpcCredential $credential, array $options = [])
    {
        $this->bitcoind = $bitcoind;
        $this->dataDir = $dataDir;
        $this->network = $network;
        $this->credential = $credential;
        $this->options = array_merge($options, $this->defaultOptions);
    }

    /**
     * @return string
     */
    private function getPidFile(): string
    {
        return "{$this->dataDir}/regtest/bitcoind.pid";
    }

    /**
     * @return string
     */
    private function getConfigFile(): string
    {
        return "{$this->dataDir}/bitcoin.conf";
    }

    private function secondsToMicro(float $seconds): int
    {
        return (int) $seconds * 1000000;
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

        $config = array_merge(
            $this->options,
            $rpcCredential->getConfigArray()
        );

        $iniConfig = implode("\n", array_map(function ($value, $key) {
            return "{$key}={$value}";
        }, $config, array_keys($config)));

        if (!fwrite($fd, $iniConfig)) {
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
                    if ($result['error']['code'] !== self::ERROR_STARTUP) {
                        throw new \RuntimeException("Unexpected error code during startup");
                    }

                    // 0.2 seconds sleep
                    usleep($this->secondsToMicro(0.02));
                }
            } catch (\Exception $e) {
                // 0.2 seconds sleep
                usleep($this->secondsToMicro(0.02));
            }

            if (microtime(true) > $start + $limit) {
                throw new \RuntimeException("Timeout elapsed, never made connection to bitcoind");
            }
        } while (!$connected);
    }

    /**
     * @return Client
     */
    private function getClient(): Client
    {
        $client = new Client($this->credential->getDsn());
        $client->withDriver(new CurlDriver());
        return $client;
    }

    private function activateSoftforks()
    {
        if ($this->softforks) {
            return;
        }

        $chainInfo = $this->makeRpcRequest('getblockchaininfo');
        $bestHeight = $chainInfo['result']['blocks'];

        while ($bestHeight < 150 || $chainInfo['result']['bip9_softforks']['segwit']['status'] !== 'active') {
            // ought to finish in 1!
            $this->makeRpcRequest("generate", [435]);
            $chainInfo = $this->makeRpcRequest('getblockchaininfo');
            $bestHeight = $chainInfo['result']['blocks'];
        }

        $this->softforks = true;
    }

    /**
     * @param int $value
     * @param ScriptInterface $script
     * @return Utxo
     */
    public function fundOutput(int $value, ScriptInterface $script)
    {
        $this->activateSoftforks();

        $builder = new TxBuilder();
        $builder->output($value, $script);
        $hex = $builder->get()->getHex();

        $result = $this->makeRpcRequest('fundrawtransaction', [$hex, ['feeRate'=>0.0001]]);
        $unsigned = $result['result']['hex'];
        $result = $this->makeRpcRequest('signrawtransaction', [$unsigned]);
        $signedHex = $result['result']['hex'];
        $signed = TransactionFactory::fromHex($signedHex);

        $outIdx = -1;
        foreach ($signed->getOutputs() as $i => $output) {
            if ($output->getScript()->equals($script)) {
                $outIdx = $i;
            }
        }

        if ($outIdx === -1) {
            throw new \RuntimeException("Sanity check failed, should have found the output we funded");
        }

        $result = $this->makeRpcRequest('sendrawtransaction', [$signedHex]);
        $txid = $result['result'];
        $this->makeRpcRequest("generate", [1]);

        return new Utxo(new OutPoint(Buffer::hex($txid), $outIdx), new TransactionOutput($value, $script));
    }

    /**
     * @param string $src
     */
    private function recursiveDelete(string $src)
    {
        $dir = opendir($src);
        while (false !== ( $file = readdir($dir))) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    $this->recursiveDelete($full);
                } else {
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
                usleep($this->secondsToMicro(0.02));
            } while ($this->isRunning());

            $this->recursiveDelete($this->dataDir);
        }
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return file_exists($this->getPidFile());
    }

    /**
     * @return Client
     */
    public function makeClient(): Client
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
     * @return array
     */
    public function request(string $method, array $params = []): array
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
     * @return array
     */
    public function makeRpcRequest(string $method, array $params = []): array
    {
        return $this->request($method, $params);
    }
}
