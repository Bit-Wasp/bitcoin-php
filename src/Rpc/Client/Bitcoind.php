<?php

namespace Afk11\Bitcoin\Rpc\Client;

use Afk11\Bitcoin\JsonRpc\JsonRpcClient;
use Afk11\Bitcoin\Transaction\Transaction;

class Bitcoind
{
    /**
     * @var JsonRpcClient
     */
    protected $client;

    /**
     * @param JsonRpcClient $client
     */
    public function __construct(JsonRpcClient $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @param $txid
     * @param bool $verbose
     * @return Transaction|mixed
     */
    public function getrawtransaction($txid, $verbose = false)
    {
        $tx = $this->client->execute('getrawtransaction', array($txid));

        if ($verbose) {
            $tx = Transaction::fromHex($tx);
        }

        return $tx;
    }
}
