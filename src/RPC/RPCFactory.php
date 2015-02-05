<?php

namespace Bitcoin\RPC;

use Bitcoin\JsonRPC\JsonRPCClient;
use Bitcoin\RPC\Client\Bitcoind;

class RPCFactory
{
    public static function bitcoind($host, $port, $user, $password, $timeout = 5, $headers = array())
    {

        $jsonRPCclient = new JsonRPCClient($host, $port, $timeout, $headers);

        if (!is_null($user) && !is_null($password)) {
            $jsonRPCclient->authentication($user, $password);
        }

        $bitcoind = new Bitcoind($jsonRPCclient);

        return $bitcoind;
    }
}
