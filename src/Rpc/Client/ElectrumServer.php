<?php

namespace BitWasp\Bitcoin\Rpc\Client;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;
use BitWasp\Stratum\Client;
use BitWasp\Stratum\Request\Response;

class ElectrumServer
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Math
     */
    private $math;

    /**
     * @param Client $client
     */
    public function __construct(Math $math, Client $client)
    {
        $this->client = $client;
        $this->math = $math;
    }

    /**
     * @param TransactionInterface $transaction
     * @return \React\Promise\Promise
     */
    public function transactionBroadcast(TransactionInterface $transaction)
    {
        return $this->client->request('blockchain.transaction.broadcast', [$transaction->getHex()]);
    }

    /**
     * @param $txid
     * @return \React\Promise\Promise
     */
    public function transactionGet($txid)
    {
        return $this->client->request('blockchain.transaction.get', [$txid])
            ->then(function (Response $response) {
                return TransactionFactory::fromHex($response->getResult());
            });
    }

    /**
     * @param $txid
     * @return \React\Promise\Promise
     */
    public function transactionGetMerkle($txid, $height)
    {
        return $this->client->request('blockchain.transaction.get_merkle', [$txid, $height]);
    }

    /**
     * @param AddressInterface $address
     * @param NetworkInterface $network
     * @return \React\Promise\Promise
     */
    public function addressGetHistory(AddressInterface $address, NetworkInterface $network = null)
    {
        return $this->client->request('blockchain.address.get_history', [$address->getAddress($network)]);
    }

    /**
     * @param AddressInterface $address
     * @param NetworkInterface $network
     * @return \React\Promise\Promise
     */
    public function addressGetBalance(AddressInterface $address, NetworkInterface $network = null)
    {
        return $this->client->request('blockchain.address.get_balance', [$address->getAddress($network)]);
    }

    /**
     * @param AddressInterface $address
     * @param NetworkInterface $network
     * @return \React\Promise\Promise
     */
    public function addressGetProof(AddressInterface $address, NetworkInterface $network = null)
    {
        return $this->client->request('blockchain.address.get_proof', [$address->getAddress($network)]);
    }

    /**
     * @param AddressInterface $address
     * @param NetworkInterface $network
     * @return \React\Promise\Promise
     */
    public function addressListUnspent(AddressInterface $address, NetworkInterface $network = null)
    {
        return $this->client->request('blockchain.address.listunspent', [$address->getAddress($network)])
            ->then(function (Response $response) use ($address) {
                return array_map(
                    function (array $value) use ($address) {
                        return new Utxo(
                            $value['tx_hash'],
                            $value['tx_pos'],
                            new TransactionOutput(
                                $value['value'],
                                ScriptFactory::scriptPubKey()->payToAddress($address)
                            )
                        );
                    },
                    $response->getResult()
                );
            });
    }

    /**
     * @param string $txid
     * @param int|string $vout
     * @return \React\Promise\Promise
     */
    public function utxoGetAddress($txid, $vout)
    {
        return $this->client->request('blockchain.utxo.get_address', [$txid, $vout]);
    }

    /**
     * @param $height
     * @return \React\Promise\Promise
     */
    public function blockGetHeader($height)
    {
        return $this->client->request('blockchain.block.get_header', [$height])
            ->then(function (\BitWasp\Stratum\Request\Response $response) {
                $content = $response->getResult();
                return new \BitWasp\Bitcoin\Block\BlockHeader(
                    $content['version'],
                    @$content['prev_block_hash'],
                    $content['merkle_root'],
                    $content['timestamp'],
                    Buffer::hex($this->math->decHex($content['bits'])),
                    $content['nonce']
                );
            });
    }

    /**
     * @param $height
     * @return \React\Promise\Promise
     */
    public function blockGetChunk($height)
    {
        return $this->client->request('blockchain.block.get_chunk', [$height]);
    }
}
