<?php

namespace BitWasp\Bitcoin\Rpc;

use BitWasp\Bitcoin\JsonRpc\JsonRpcClient;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Rpc\Client\ElectrumServer;
use BitWasp\Stratum\Request\RequestFactory;
use BitWasp\Stratum\Factory;
use BitWasp\Bitcoin\Rpc\Client\Bitcoind;

class RpcFactory
{
    /**
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     * @param int $timeout
     * @param array $headers
     * @return Bitcoind
     */
    public static function bitcoind($host, $port, $user, $password, $timeout = 5, $headers = array())
    {
        $jsonRPCclient = new JsonRpcClient($host, $port, $timeout, $headers);
        $jsonRPCclient->authentication($user, $password);

        return new Bitcoind($jsonRPCclient);
    }

    /**
     * @param Math $math
     * @param \React\EventLoop\LoopInterface $loop
     * @param $host
     * @param $port
     * @param int $timeout
     * @return ElectrumServer
     */
    public static function electrum(Math $math, \React\EventLoop\LoopInterface $loop, $host, $port, $timeout = 5)
    {
        $clientFactory = new Factory(
            $loop,
            new \React\SocketClient\Connector(
                $loop,
                (new \React\Dns\Resolver\Factory())->create('8.8.8.8', $loop)
            ),
            new RequestFactory
        );

        $client = $clientFactory->create($host, $port, $timeout);

        return new ElectrumServer($math, $client);
    }
}
