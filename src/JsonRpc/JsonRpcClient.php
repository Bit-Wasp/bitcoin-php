<?php

namespace BitWasp\Bitcoin\JsonRpc;

use BitWasp\Bitcoin\Exceptions\JsonRpcError;
use JsonRPC\Client;

class JsonRpcClient extends Client
{
    /**
     * @param $host
     * @param $port
     * @param int $timeout
     * @param array $headers
     */
    public function __construct($host, $port, $timeout = 5, array $headers = array())
    {
        $url = "http://$host:$port/";
        parent::__construct($url, $timeout, $headers);
    }

    /**
     * Get a RPC call result
     *
     * @access public
     * @param  array    $payload
     * @return mixed
     */
    public function getResult(array $payload)
    {
        if (isset($payload['error']['code'])) {
            $this->handleRpcErrors($payload['error']);
        }

        return isset($payload['result']) ? $payload['result'] : null;
    }

    /**
     * Throw an exception according the RPC error
     *
     * @access public
     * @param $error
     * @throws JsonRpcError
     */
    public function handleRpcErrors($error)
    {
        switch ($error['code']) {
            case -32601:
                throw new \BadFunctionCallException('Procedure not found: ' . $error['message']);
            case -32602:
                throw new \InvalidArgumentException('Invalid arguments: ' . $error['message']);
            default:
                throw new JsonRpcError($error['message'], $error['code']);
        }
    }
}
