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

        if (!is_null($user) && !is_null($password)) {
            $jsonRPCclient->authentication($user, $password);
        }

        $bitcoind = new Bitcoind($jsonRPCclient);

        return $bitcoind;
    }
}
