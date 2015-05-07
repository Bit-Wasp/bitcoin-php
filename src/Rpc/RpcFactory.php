<?php

namespace BitWasp\Bitcoin\Rpc;

use BitWasp\Bitcoin\JsonRpc\JsonRpcClient;
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
}
