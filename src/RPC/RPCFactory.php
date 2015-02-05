<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 01/02/15
 * Time: 18:15
 */

namespace Bitcoin\RPC;

use Bitcoin\Bitcoin;
use Bitcoin\JsonRPC\JsonRPCClient;
use Bitcoin\RPC\Client\Bitcoind;

class RPCFactory
{
    public static function bitcoind($host, $port, $timeout = 5, $headers = array(), $user, $password) {
        $jsonRPCclient = new JsonRPCClient($host, $port, $timeout, $headers);
        $jsonRPCclient->authentication($user, $password);

        $bitcoind = new Bitcoind($jsonRPCclient);

        return $bitcoind;
    }
}