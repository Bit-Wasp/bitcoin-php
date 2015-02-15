<?php

namespace Bitcoin\Rpc\Client;

use Bitcoin\JsonRPC\JsonRPCClient;
use Bitcoin\Transaction\Transaction;

class Bitcoind
{
    /**
     * @var JsonRPCClient
     */
    protected $client;

    public function __construct(JsonRPCClient $client)
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
