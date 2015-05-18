<?php

namespace BitWasp\Bitcoin\Rpc;

use BitWasp\Bitcoin\JsonRpc\JsonRpcClient;
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

    public static function electrum(\React\EventLoop\LoopInterface $loop, $host, $port, $timeout = 5)
    {
        // Initialize react event loop, resolver, and connector
        $connector = new \React\SocketClient\Connector(
            $loop,
            (new \React\Dns\Resolver\Factory())->create('8.8.8.8', $loop)
        );

        $request = new RequestFactory;
        $clientFactory = new Factory($loop, $connector, $request);
        $stratum = $clientFactory->create($host, $port);

    }
}
