<?php

namespace Bitcoin\Rpc\Client;

use Afk11\Bitcoin\JsonRpc\JsonRpcClient;
use Bitcoin\Transaction\Transaction;

class Bitcoind
{
    /**
     * @var JsonRpcClient
     */
    protected $client;

    public function __construct(JsonRpcClient $client)
    {
        $this->client = $client;
        return $this;
    }

    public function gettransaction($txid)
    {

    }

    public function getrawtransaction($txid, $verbose = false)
    {
        $tx = $this->client->execute('getrawtransaction', array($txid));

        if ($verbose) {
            $tx = Transaction::fromHex($tx);
        }

        return $tx;
    }
}
