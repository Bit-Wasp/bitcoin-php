<?php

namespace BitWasp\Bitcoin\Rpc\Client;


use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Stratum\Client;

class ElectrumServer
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->cleint = $client;
    }

    public function transactionBroadcast(TransactionInterface $transaction)
    {

    }

    public function transactionGet($txid)
    {

    }

    public function transactionGetMerkle($txid)
    {

    }

    public function addressGetHistory(AddressInterface $address)
    {

    }

    public function addressGetProof(AddressInterface $address)
    {

    }

    public function addressListUnspent(AddressInterface $address)
    {

    }

    public function utxoGetAddress($txid, $vout)
    {

    }

    public function blockGetHeader($height)
    {

    }

    public function blockGetChunk($height)
    {

    }
}