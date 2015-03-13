<?php

namespace Afk11\Bitcoin\JsonRpc;

use JsonRPC\Client;

class JsonRpcClient extends Client
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @param $host
     * @param $port
     * @param int $timeout
     * @param array $headers
     */
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

    /**
     * @param $username
     * @param $password
     * @return $this
     */
    public function authentication($username, $password)
    {
        $this->client->authentication($username, $password);
        return $this;
    }

    /**
     * @return $this
     */
    public function batch()
    {
        $this->client->batch();
        return $this;
    }

    /**
     * @return array
     */
    public function send()
    {
        return $this->client->send();
    }

    /**
     * @param $command
     * @param array $arguments
     * @return mixed
     */
    public function execute($command, array $arguments = array())
    {
        return $this->client->execute($command, $arguments);
    }

    /**
     * @param $procedure
     * @param array $params
     * @return array
     */
    public function prepareRequest($procedure, array $params = array())
    {
        return $this->client->prepareRequest($procedure, $params);
    }

    /**
     * @param array $payload
     * @return mixed
     */
    public function parseResponse(array $payload)
    {
        return $this->client->parseResponse($payload);
    }

    /**
     * @param array $payload
     * @return mixed
     */
    public function getResult(array $payload)
    {
        return $this->client->getResult($payload);
    }

    /**
     * @param $code
     */
    public function handleRpcErrors($code)
    {
        $this->client->handleRpcErrors($code);
    }

    /**
     * @param $payload
     */
    public function doRequest($payload)
    {
        $this->client->doRequest($payload);
    }
}
