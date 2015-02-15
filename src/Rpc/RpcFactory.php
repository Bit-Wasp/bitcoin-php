<?php

namespace Afk11\Bitcoin\Rpc;

use Afk11\Bitcoin\JsonRpc\JsonRpcClient;
use Afk11\Bitcoin\Rpc\Client\Bitcoind;

class RpcFactory
{
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
