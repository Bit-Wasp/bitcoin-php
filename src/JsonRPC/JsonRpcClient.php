<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 01/02/15
 * Time: 18:48
 */

namespace Afk11\Bitcoin\JsonRpc;

use JsonRPC\Client;

class JsonRpcClient
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct($host, $port, $timeout = 5, array $headers = array())
    {
        $this->client = new Client("http://$host:$port/", $timeout, $headers);
        return $this;
    }

    /**
     * Automatic mapping of procedures
     *
     * @access public
     * @param  string   $method   Procedure name
     * @param  array    $params   Procedure arguments
     * @return mixed
     */
    public function __call($method, array $params)
    {
        // Allow to pass an array and use named arguments
        if (count($params) === 1 && is_array($params[0])) {
            $params = $params[0];
        }

        return $this->client->execute($method, $params);
    }

    public function authentication($username, $password)
    {
        $this->client->authentication($username, $password);
        return $this;
    }

    public function batch()
    {
        $this->client->batch();
        return $this;
    }

    public function send()
    {
        $this->client->batch();
        return $this;
    }

    public function execute($command, array $arguments = array())
    {
        return $this->client->execute($command, $arguments);
    }

    public function prepareRequest($procedure, array $params = array())
    {
        return $this->client->prepareRequest($procedure, $params);
    }

    public function parseResponse(array $payload)
    {
        return $this->client->parseResponse($payload);
    }

    public function getResult(array $payload)
    {
        return $this->client->getResult($payload);
    }

    public function handleRpcErrors($code)
    {
        $this->client->handleRpcErrors($code);
    }

    public function doRequest($payload)
    {
        $this->client->doRequest($payload);
    }
}
